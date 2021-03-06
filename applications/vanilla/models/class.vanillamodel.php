<?php if (!defined('APPLICATION')) exit();

/**
 * The VanillaModel introduces common methods that child classes can use.
 */
abstract class Gdn_VanillaModel extends Gdn_Model {
   /**
    * Class constructor.
    */
   public function __construct($Name = '') {
      parent::__construct($Name);
   }
   
   /**
    * Checks to see if the user is spamming. Returns TRUE if the user is spamming.
    */
   public function CheckForSpam($Type) {
      $Spam = FALSE;
      if (!in_array($Type, array('Comment', 'Discussion')))
         trigger_error(ErrorMessage(sprintf('Spam check type unknown: %s', $Type), 'VanillaModel', 'CheckForSpam'), E_USER_ERROR);
      
      $Session = Gdn::Session();
      $CountSpamCheck = $Session->GetAttribute('Count'.$Type.'SpamCheck', 0);
      $DateSpamCheck = $Session->GetAttribute('Date'.$Type.'SpamCheck', 0);
      $SecondsSinceSpamCheck = time() - Format::ToTimestamp($DateSpamCheck);
         
      $SpamCount = Gdn::Config('Vanilla.'.$Type.'.SpamCount');
      if (!is_numeric($SpamCount) || $SpamCount < 2)
         $SpamCount = 2; // 2 spam minimum

      $SpamTime = Gdn::Config('Vanilla.'.$Type.'.SpamTime');
      if (!is_numeric($SpamTime) || $SpamTime < 0)
         $SpamTime = 30; // 30 second minimum spam span
         
      $SpamLock = Gdn::Config('Vanilla.'.$Type.'.SpamLock');
      if (!is_numeric($SpamLock) || $SpamLock < 30)
         $SpamLock = 30; // 30 second minimum lockout

      // Definition:
      // Users cannot post more than $SpamCount comments within $SpamTime
      // seconds or their account will be locked for $SpamLock seconds.

      // Apply a spam lock if necessary
      $Attributes = array();
      if ($SecondsSinceSpamCheck < $SpamLock && $CountSpamCheck >= $SpamCount && $DateSpamCheck !== FALSE) {
         // TODO: REMOVE DEBUGGING INFO AFTER THIS IS WORKING PROPERLY
         /*
         echo '<div>SecondsSinceSpamCheck: '.$SecondsSinceSpamCheck.'</div>';
         echo '<div>SpamLock: '.$SpamLock.'</div>';
         echo '<div>CountSpamCheck: '.$CountSpamCheck.'</div>';
         echo '<div>SpamCount: '.$SpamCount.'</div>';
         echo '<div>DateSpamCheck: '.$DateSpamCheck.'</div>';
         echo '<div>SpamTime: '.$SpamTime.'</div>';
         */
         $Spam = TRUE;
         $this->Validation->AddValidationResult(
            'Body',
            sprintf(
               Gdn::Translate('You have posted %1$s times within %2$s seconds. A spam block is now in effect on your account. You must wait at least %3$s seconds before attempting to post again.'),
               $SpamCount,
               $SpamTime,
               $SpamLock
            )
         );
         
         // Update the 'waiting period' every time they try to post again
         $Attributes['Date'.$Type.'SpamCheck'] = Format::ToDateTime();
      } else {
         if ($SecondsSinceSpamCheck > $SpamTime) {
            $Attributes['Count'.$Type.'SpamCheck'] = 1;
            $Attributes['Date'.$Type.'SpamCheck'] = Format::ToDateTime();
         } else {
            $Attributes['Count'.$Type.'SpamCheck'] = $CountSpamCheck + 1;
         }
      }
      // Update the user profile after every comment
      $UserModel = Gdn::UserModel();
      $UserModel->SaveAttribute($Session->UserID, $Attributes);
      
      return $Spam;
   }   
}