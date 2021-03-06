<?php

namespace App;

use Illuminate\Support\Str;
use Illuminate\Contracts\Support\Htmlable;

class Website extends ContactDetails implements Htmlable
{
    /**
     * The type of contact info stored by the object.
     *
     * @var string
     */
    protected $type = 'website';

    /**
     * The URL of the website.
     *
     * @var string
     */
    protected $url;

    /**
     * Whether or not the URL uses HTTPS.
     *
     * @var bool
     */
    protected $useHttps = false;

    /**
     * Create a new instance from a URL.
     *
     * @param  string  $url
     *
     * @return self
     */
    public static function fromUrl($url)
    {
        $site = new self;

        $site->url = $site->clean($url);

        if (Str::startsWith($url, 'https://')) {
            $site->useHttps = true;
        }

        return $site;
    }

    protected function clean($url)
    {
        $url = trim($url);

        $url = str_replace(['http://', 'https://'], $replace = '', $url);

        return $url;
    }

    /**
     * Build the URL from the network and the handle.
     *
     * @return string
     */
    protected function getUrl()
    {
        $protocol = $this->useHttps ? 'https' : 'http';

        return $protocol.'://'.$this->clean($this->url);
    }

    /**
     * Convert the website to an <a> HTML element pointing to its URL.
     *
     * The text of the link is the URL of the website, without its protocol.
     *
     * @return string
     */
    public function asLink()
    {
        return (string) app('html')->link($this->getUrl(), $this->urlWithoutProtocol);
    }

    /**
     * Allows the object to be automatically converted to an HTML link
     * when echoed in a Blade template.
     *
     * @return string
     */
    public function toHtml()
    {
        return $this->asLink();
    }

    /**
     * Get the key-value pairs of data that are specific to this type of contact info.
     *
     * @return array
     */
    protected function getOwnAttributes()
    {
        return [
            'url' => $this->getUrl(),
        ];
    }

    /**
     * Little hack to ensure that some properties are correctly set when
     * initializing a website from data that is already stored in the
     * database.
     */
    protected function populateContactDetailProperties()
    {
        parent::populateContactDetailProperties();

        if (Str::startsWith($this->url, 'https://')) {
            $this->useHttps = true;
        }

        $this->url = $this->clean($this->url);
    }

    /**
     * Allow to read specific properties as if they were public.
     *
     * @param  string  $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        switch ($name) {
            case 'url':
                return $this->getUrl();
            case 'urlWithoutProtocol':
                return $this->url;
            case 'useHttps':
                return (bool) $this->useHttps;
        }

        return parent::__get($name);
    }

    /**
     * Allow to write specific properties as if they were public.
     *
     * @param string  $name
     * @param mixed   $value
     */
    public function __set($name, $value)
    {
        switch ($name) {
            case 'url':
                $this->url = $this->clean($value);

                $this->useHttps = (bool) Str::startsWith($this->url, 'https://');

                break;
            case 'urlWithoutProtocol':
                $this->urlWithoutProtocol = $value;
                break;
            case 'useHttps':
                $this->useHttps = (bool) $value;
                break;
            default:
                // Fall back on the magic method of the parent.
                parent::__set($name, $value);
                break;
        }
    }

    /**
     * Convert the website to its string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getUrl();
    }
}
