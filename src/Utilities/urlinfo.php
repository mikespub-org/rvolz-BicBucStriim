<?php

namespace BicBucStriim\Utilities;

/**
 * Class UrlInfo contains information on how to construct URLs
 */
class UrlInfo
{
    /**
     * @var string $protocol - protocol used for access, default 'http'
     */
    public $protocol = 'http';
    /**
     * @var string $host - hostname or ip address used for access
     */
    public $host;

    public function __construct()
    {
        $na = func_num_args();
        if ($na == 2) {
            $fhost = func_get_arg(0);
            if (!is_null($fhost) && $fhost != 'unknown') {
                $this->host = $fhost;
            }
            $fproto = func_get_arg(1);
            if (!is_null($fproto)) {
                $this->protocol = $fproto;
            }
        } else {
            $ffw = func_get_arg(0);
            $ffws = preg_split('/;/', $ffw, -1, PREG_SPLIT_NO_EMPTY);
            $opts = [];
            foreach ($ffws as $ffwi) {
                $ffwis = preg_split('/=/', $ffwi, -1);
                $opts[$ffwis[0]] = $ffwis[1];
            }
            if (isset($opts['by'])) {
                $this->host = $opts['by'];
            }
            if (isset($opts['proto'])) {
                $this->protocol = $opts['proto'];
            }
        }
    }

    public function __toString()
    {
        return "UrlInfo{ protocol: $this->protocol, host: $this->host}";
    }

    public function is_valid()
    {
        return (!empty($this->host));
    }

    /**
     * Return a UrlInfo instance if the request contains forwarding information, or null if not.
     *
     * First we look for the standard 'Forwarded' header from RFC 7239, then for the non-standard X-Forwarded-... headers.
     *
     * @param \Slim\Http\Headers $headers
     * @return null|self
     */
    public static function getForwardingInfo($headers)
    {
        $info = null;
        $forwarded = $headers->get('Forwarded');
        if (!is_null($forwarded)) {
            $info = new self($forwarded);
        } else {
            $fhost = $headers->get('X-Forwarded-Host');
            $fproto = $headers->get('X-Forwarded-Proto');
            if (!is_null($fhost)) {
                $info = new self($fhost, $fproto);
            }
        }
        return $info;
    }
}
