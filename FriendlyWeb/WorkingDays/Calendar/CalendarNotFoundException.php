<?php
/**
 * Alexander Dalle
 * dalle@criptext.com
 * 
 */

namespace FriendlyWeb;

use Exception;

class CalendarNotFoundException extends Exception 
{
    public function __construct(string $calendarFile, string $message = "")
    {
        if (empty($message)) {
            $message = "Календарь не найден: {$calendarFile}";
        }
        
        parent::__construct($message);
    }
}