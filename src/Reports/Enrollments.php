<?php

namespace Greystar\Reports;

use \Greystar\Dto\Reports\ {
    Enrollments\Request as EnrollmentsDto,
	Enrollments\Response as EnrollmentsOutDto
};
/**
 * @description 
 */
class Enrollments extends \Greystar\Reports
{
    private $startdate, $enddate, $distid;
    /**
     * @description 
     */
    public function setDistId($distid)
    {
        $this->distid = $distid;
        return $this;
    }
    /**
     * @description This will fetch today's date only
     */
    public function getNew($date)
    {
        $this->startdate = $this->enddate = $date;
        # Use this report so that it has a record of the sign up purchasing something
        $report = $this->getReport(new EnrollmentsDto([ 'startdate' => $date, 'enddate' => $date ]),
            null,
            function () {
                $args = func_get_args();
                if (empty($args[0]))
                    return [];
                $csv = array_map(function ($v) {
                    return str_getcsv($v, "\t");
                }, explode(PHP_EOL, $args[0]));
                return $this->buildCsvWithProducts($csv, 36);
            }
        );

        return \Nubersoft\ArrayWorks::organizeByKey($report, 'first_invoice');
    }
    /**
     * @description This will fetch a date range, default being first of the month to today
     */
    public function get(Callable $func = null, string $start = null, string $end = null): array
    {
        $this->startdate = (!empty($start)) ? $start : date('Y-m') . '-01';
        $this->enddate = (!empty($end)) ? $end : date('Y-m-d');
        $params = [
            'startdate' => $this->startdate,
            'enddate' => $this->enddate
        ];

        if (!empty($this->distid)) {
            $params['username'] = $this->distid;
        }

        $report = $this->getReport(new EnrollmentsDto($params), null, function () {
            $args = func_get_args();
            if (empty($args[0]))
                return [];

            $csv = array_map(function ($v) {
                return str_getcsv($v, "\t");
            }, explode(PHP_EOL, $args[0]));

            return $csv;
        });

        $new = [];
        $head = array_map(function ($v) {
            return str_replace([' ', '(', ')'], ['_', '', ''], strtolower($v));
        }, $report[0]);
        unset($report[0]);
        $c = count($head);
        foreach ($report as $row) {
            $rowArr = array_slice($row, 0, $c);

            if (count($rowArr) != count($head))
                continue;
            $arr = new EnrollmentsOutDto(array_combine($head, $rowArr));
            $new[] = $arr;
        }

        return ($func) ? $func(array_filter($new), $this) : array_filter($new);
    }
    /**
     * @description 
     */
    public function getDateRange()
    {
        return [
            'start' => $this->startdate,
            'end' => $this->enddate
        ];
    }
}
