<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Mark O'Sullivan
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Mark O'Sullivan at mark [at] lussumo [dot] com
*/

if (!isset($Drop))
   $Drop = FALSE;
   
if (!isset($Explicit))
   $Explicit = TRUE;

// Contains all conversations. A conversation takes place between X number of
// ppl. This table keeps track of the unique id of the conversation, the person
// who started the conversation (and when), and the last person to contribute to
// the conversation (and when).
// Column($Name, $Type, $Length = '', $Null = FALSE, $Default = NULL, $KeyType = FALSE, $AutoIncrement = FALSE)
$Construct->Table('Conversation')
   ->Column('ConversationID', 'int', 11, FALSE, NULL, 'primary', TRUE)
   ->Column('Contributors', 'varchar', 255)
   ->Column('FirstMessageID', 'int', 11, TRUE, NULL, 'key')
   ->Column('InsertUserID', 'int', 10, FALSE, NULL, 'key')
   ->Column('DateInserted', 'datetime')
   ->Column('UpdateUserID', 'int', 10, FALSE, NULL, 'key')
   ->Column('DateUpdated', 'datetime')
   ->Set($Explicit, $Drop);

// Contains the user/conversation relationship. Keeps track of all users who are
// taking part in the conversation. It also keeps DateCleared, which is a
// per-user date relating to when each users last cleared the conversation
// history, and 
$Construct->Table('UserConversation')
   ->Column('UserID', 'int', 11, FALSE, NULL, 'primary')
   ->Column('ConversationID', 'int', 11, FALSE, NULL, 'primary')
   ->Column('CountNewMessages', 'int', 11, FALSE, '0') // # of unread messages
   ->Column('CountMessages', 'int', 11, FALSE, '0') // # of uncleared messages
   ->Column('LastMessageID', 'int', 11, TRUE, NULL, 'key') // The last message posted by a user other than this one, unless this user is the only person who has added a message
   ->Column('DateLastViewed', 'datetime', '', TRUE)
   ->Column('DateCleared', 'datetime', '', TRUE)
   ->Column('Bookmarked', array('1', '0'), '', FALSE, '0')
   ->Set($Explicit, $Drop);
   
// Contains messages for each conversation, as well as who inserted the message
// and when it was inserted. Users cannot edit or delete their messages once
// they have been sent.
$Construct->Table('ConversationMessage')
   ->Column('MessageID', 'int', 11, FALSE, NULL, 'primary', TRUE)
   ->Column('ConversationID', 'int', 11)
   ->Column('Body', 'text')
   ->Column('Format', 'varchar', 20, TRUE)
   ->Column('InsertUserID', 'int', 10, FALSE, NULL, 'key')
   ->Column('DateInserted', 'datetime')
   ->Set($Explicit, $Drop);
   
// Add extra columns to user table for tracking discussions, comments & replies
$Construct->Table('User')
   ->Column('CountUnreadConversations', 'int', 11, FALSE, '0')
   ->Set();
   
// Insert some activity types
///  %1 = ActivityName
///  %2 = ActivityName Possessive
///  %3 = RegardingName
///  %4 = RegardingName Possessive
///  %5 = Link to RegardingName's Wall
///  %6 = his/her
///  %7 = he/she
///  %8 = RouteCode & Route (will be changed to <a href="route">routecode</a>)

// X sent you a message
$SQL = $Database->SQL();
if ($SQL->GetWhere('ActivityType', array('Name' => 'ConversationMessage'))->NumRows() == 0)
   $SQL->Insert('ActivityType', array('AllowComments' => '0', 'Name' => 'ConversationMessage', 'FullHeadline' => '%1$s sent you a %8$s.', 'ProfileHeadline' => '%1$s sent you a %8$s.', 'RouteCode' => 'message', 'Notify' => '1', 'Public' => '0'));
   
if ($SQL->GetWhere('ActivityType', array('Name' => 'AddedToConversation'))->NumRows() == 0)
   $SQL->Insert('ActivityType', array('AllowComments' => '0', 'Name' => 'AddedToConversation', 'FullHeadline' => '%1$s added you to a %8$s.', 'ProfileHeadline' => '%1$s added  you to a %8$s.', 'RouteCode' => 'conversation', 'Notify' => '1', 'Public' => '0'));