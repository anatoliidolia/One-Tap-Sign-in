<?php
declare(strict_types=1);

namespace PeachCode\GoogleOneTap\Model\API;

interface DataInterface
{
    public const XML_CLIENT_ID = 'googleonetap/general/client_id';
    public const XML_BCG_CLICK = 'googleonetap/general/background_click';
    public const XML_AUTO_SIGN_IN = 'googleonetap/general/auto_signin';
    public const XML_POSITION = 'googleonetap/general/position';
    public const XML_STATUS = 'googleonetap/module_status/status';
}
