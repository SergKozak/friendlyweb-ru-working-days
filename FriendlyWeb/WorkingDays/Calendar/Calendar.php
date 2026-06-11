<?php
/**
 * Alexander Dalle
 * dalle@criptext.com
 * 
 */

namespace FriendlyWeb;

use DateTime;

class Calendar extends DateTime 
{

    // где лежат все календари в формате JSON
    private string $calendarDir = __DIR__ . "/../data" . DIRECTORY_SEPARATOR . "russian" . DIRECTORY_SEPARATOR;
    private mixed $calendar = null;
    private ?string $day = null;
    private ?string $month = null;
    private ?string $year = null;
    private array $i18n = array(
        "error_file" => "Календарь не найден! Проверьте правильно ли указана директория.",
        "holiday" => "Выходной день"
    );

    public function __construct(string $datetime = 'NOW')
    {
        parent::__construct($datetime);
        $this->setDay($datetime);
    }



    /**
     * Метод проверят входит ли в диапазон чисел (Например, "1-5") число
     * Возвращает boolean
     */
    private function checkRange(string $range, int $number): bool
    {
        $range = explode('-', $range);
        $range[1] = $range[1] ?? null;

        if ($range[1]) {
            for ($i = (int)$range[0]; $i <= (int)$range[1]; $i++) {
                if ($i == $number) {
                    return true;
                }
            }

            return false;
        }

        if ($range[0] == $number) {
            return true;
        }

        return false;
    }

    /**
     * Метод устанавливает в соответствии с установленным годом
     * Возвращает boolean
     * @throws CalendarNotFoundException
     */
    private function setCalendar(): bool
    {
        if (!$this->day || !$this->month || !$this->year) {
            $this->setDay("now");
        }

        $calendarFile = $this->calendarDir . $this->year . ".json";

        if (file_exists($calendarFile)) {
          $contents = file_get_contents($calendarFile);
          $obj = json_decode($contents);

          $this->calendar = $obj;

          return true;

        }

        throw new CalendarNotFoundException($calendarFile, $this->i18n['error_file']);
    }


    /**
     * Метод проверят выходной день $preHoliday для сокращенного дня
     * Возвращает boolean
     * @throws CalendarNotFoundException
     */
    private function isRestDay(bool $preHoliday = false): bool
    {
        if (!$this->setCalendar()) {
            return false;
        }

        if (!isset($this->calendar->{$this->month})) {
            return false;
        }

        $currentMonth = $this->calendar->{$this->month};

        foreach ($currentMonth as $dayNumber => $day) {
            if (!$this->checkRange($dayNumber, (int)$this->day)) {
                continue;
            }

            if (($currentMonth->{$dayNumber}->rest) && (!$preHoliday)) {
                return true;
            }

            if ((!$currentMonth->{$dayNumber}->rest) && ($preHoliday)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Метод устаналивает дату
     */
    public function setDay(string $datetime = 'NOW'): void
    {
        $datetime = new DateTime($datetime);

        $this->setTimestamp($datetime->getTimestamp());

        $this->day = $datetime->format("j");
        $this->month = $datetime->format("F");
        $this->year = $datetime->format("Y");
    }

    /**
     * Метод устанавливает директорию с калнедарями
     */
    public function setCalendarDir(string $dir): void
    {
        $this->calendarDir = $dir;
    }

    /**
     * Возвращает описание праздничного дня
     * @throws CalendarNotFoundException
     */
    public function getHolidayDescription(): ?string
    {

        if (!$this->isHoliday()) {
            return null;
        }

        $holidayDescr = $this->getDescriptionInRange();

        if (!$holidayDescr) {
            return $this->i18n['holiday'];
        } 

        return $holidayDescr;
    }

    /**
     * Возвращает описание выходного дня в диапазоне дат (например, "7-8")
     * @throws CalendarNotFoundException
     */
    private function getDescriptionInRange(): ?string
    {
        $month = (array)$this->calendar->{$this->month};

        foreach ($month as $days => $obj) {
            if ($this->checkRange($days, (int)$this->day)) {
                return $obj->n ?? null;
            }
        }

        return null;
    }

    /**
     * Метод возвращает выходной день или нет
     * Возвращает boolean
     * @throws CalendarNotFoundException
     */
    public function isHoliday(): bool
    {
        return $this->isRestDay(false);
    }

    /**
     * Метод возвращает сокращенный день или нет
     * Возвращает boolean
     * @throws CalendarNotFoundException
     */
    public function isPreHoliday(): bool
    {
        return $this->isRestDay(true);
    }

    public function isWeekend(): bool
    {
        $dayOfWeek = (int) $this->format('N');

        return 6 === $dayOfWeek || 7 === $dayOfWeek;
    }

    public function isWorkingDay(): bool
    {
        return false === $this->isHoliday() && false === $this->isWeekend();
    }

    public function setTimestamp($timestamp): self
    {
        parent::setTimestamp($timestamp);
        
        // Автоматически обновляем внутренние свойства
        $this->day = $this->format("j");
        $this->month = $this->format("F");
        $this->year = $this->format("Y");
        
        return $this;
    }
}
