<?php


namespace Khalil1608\LibBundle\Traits;


use Khalil1608\LibBundle\Entity\DateTime;

trait StringTrait
{
    /**
     * @param $string
     * @return mixed|string
     */
    public function slugify($string)
    {

        $transliterator = ['Khalil1608\LibBundle\Transliterator\Transliterator', 'transliterate'];
        $urlizer = ['Khalil1608\LibBundle\Transliterator\Transliterator', 'urlize'];

        // trim generated slug as it will have unnecessary trailing space
        $slug = trim($string);

        // build the slug

        // Step 1: urlization (replace spaces by '-' etc...)
        $slug = call_user_func_array(
            $urlizer,
            [$slug, '']
        );

        // Step 3: stylize the slug
        $slug = strtolower($slug);

        return $slug;
    }

    /**
     * @param $value
     * @param $replace
     * @return int
     */
    public function replaceEmptyValue($value, $replace)
    {
        return !!$value || $value === 0 ? $value : $replace;
    }

    /**
     * @param $string
     * @return mixed|null
     */
    public function convertMatchedDateToFormattedDate($string)
    {
        // 15-{+5 month(s)} || 15-{-5 month(s)} || 15-{+2 year(s)} || 15-{-2 year(s)}
        if (preg_match('/(\d{1,2})[.\-]\{(\+{0,1}|\-{0,1})(\d*) (month|year)s{0,1}\}/', $string, $outputArray)) {

            $date = $this->convertCustomDateRegexResultToDate($outputArray);
            return $date->format('Y-m-d');

            // 15/{+5 month(s)} || 15/{-5 month(s)} || 15/{+2 year(s)} || 15/{-2 year(s)}
        } elseif (preg_match('/(\d{1,2})[.\/]\{(\+{0,1}|\-{0,1})(\d*) (month|year)s{0,1}\}/', $string, $outputArray)) {
            $date = $this->convertCustomDateRegexResultToDate($outputArray);
            return $date->format('Y/m/d');

            //{today|yesterday|tomorrow}/{+5 month(s)} || {today|yesterday|tomorrow}/{-5 month(s)}
        } elseif (preg_match('/\{(today|yesterday|tomorrow)\}[.\/]\{(\+{0,1}|\-{0,1})(\d*) (month|year)s{0,1}\}/', $string, $outputArray)) {
            $date = $this->convertCustomDateRegexResultToDate($outputArray);
            return $date->format('Y/m/d');

            //2020-08-16 || +3 months
        } elseif (preg_match('/((\d{1,4}([.\-])\d{1,2}([.\-])\d{1,4})|((\+{0,1}|\-{0,1})\d* (year|month|day|hour|minute|second)s{0,1})|(today|now))/', $string, $outputArray)) {
            return $outputArray[0];
        } else {
            return null;
        }
    }

    /**
     * @param $string
     * @return null|array
     */
    public function extractStringDateFromString($string)
    {
        if (
            preg_match('/(\d{1,2}([.\-])(\{((\+{0,1}|\-{0,1})\d* (month|year)s{0,1}\})))/', $string, $outputArray)
            || preg_match('/(\d{1,2}([.\/])(\{((\+{0,1}|\-{0,1})\d* (month|year)s{0,1}\})))/', $string, $outputArray)
            || preg_match('/(\{(today|yesterday|tomorrow)\}([.\/])(\{((\+{0,1}|\-{0,1})\d* (month|year)s{0,1}\})))/', $string, $outputArray)
            || preg_match('/((\d{1,4}([\-])\d{1,2}([\-])\d{1,4})|((\+{0,1}|\-{0,1})\d* (year|month|day|hour|minute|second)s{0,1})|(today|now))/', $string, $outputArray)
        ) {
            return $outputArray[0];
        }

        return null;
    }

    /**
     * @param $result
     * @param null $timezone
     * @return DateTime
     * @throws \Exception
     */
    private function convertCustomDateRegexResultToDate($result, $timezone = null): DateTime
    {
        $day = $result[1];
        $sign = $result[2];
        $nbToAdd = $result[3];
        $type = $result[4];

        if($timezone) {
            $timezone = new \DateTimeZone($timezone);
        }
        $date = new DateTime();

        if (str_contains($type, 'month')) {
            $date = $this->addMonthsToDate($date, $sign.$nbToAdd);
        } elseif(str_contains($type, 'year')) {
            $date = $date->modify($sign.$nbToAdd.' years');
        } else {
            $date = new DateTime($date->format('Y-m-').'01', $timezone);
            $date->modify($result[0]);
        }

        if(!is_numeric($day)) {
            $day = (new DateTime($day))->format('d');
            $lastDayOfTheMonth = (new DateTime($date->format('Y-m-d'), $timezone))->format('d');
            if($day > $lastDayOfTheMonth) {$day = $lastDayOfTheMonth;}
        }

        $date->setDate($date->format('Y'), $date->format('m'), $day);

        return $date;
    }

    private static function getClassNameWithNamespaceFromFile(string $filePath): ?string
    {
        // Read the file content
        $content = file_get_contents($filePath);

        // Use regular expressions to find the namespace and class name
        if (preg_match('/namespace\s+([^;]+);/i', $content, $namespaceMatches) &&
            preg_match('/class\s+([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)/i', $content, $classMatches)) {

            // Combine the namespace and class name
            $namespace = trim($namespaceMatches[1]);
            $className = $classMatches[1];

            if (!empty($namespace)) {
                return $namespace . '\\' . $className;
            }

            return $className;
        }

        return null;
    }
}
