<?php
namespace Greystar\Dto\Reports\Subscription\AutoShipHistory;

class Request extends \Greystar\Dto\Reports\GetReport\Request
{
    public string $reportcode = 'pastauto';
}