<?php
namespace Greystar\Dto\Reports\Enrollments;

class Response extends \SmartDto\Dto
{
    public ?string $name = null;
    public ?string $enroller_name = null;
    public ?string $enroller = null;
    protected function beforeConstruct($array)
    {
        $arr['name'] = "{$array['first']} {$array['last']}";
        $arr['enroller_name'] = $array['enroller_first'] . ' ' . $array['enroller_last'];
        $arr['enroller'] = $array['enroller_referrer'];
        return $arr;
    }
}