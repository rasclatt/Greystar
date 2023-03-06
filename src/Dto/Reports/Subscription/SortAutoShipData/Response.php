<?php
namespace Greystar\Dto\Reports\Subscription\SortAutoShipData;

class Response extends \SmartDto\Dto
{
    public int $count = 0;
    public ?string $bv_breakdown = null;
    public ?float $average_bv = null;
    public ?string $next_run = null;
    public $data;
}