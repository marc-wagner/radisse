<?php

namespace App;

use stdClass;
use DomainException;
use Illuminate\Support\Str;

class PostalAddress extends ContactDetails
{
    /**
     * The type of contact info stored by the object.
     *
     * @var string
     */
    protected $type = 'postal-address';

    /**
     * The components of the postal address.
     *
     * @var \stdClass
     */
    protected $parts;

    /**
     * Whether or not the address should be formatted for postal mail delivery.
     *
     * @var bool
     */
    public $usePostalFormat = false;

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        // When the postal address is saved to the database, if
        // its `contactable` model is a Location, we will copy
        // the city name of the address in the `city_cache`
        // column of that contactable model.
        static::saving(function (self $self) {

            $contactable = $self->contactable;

            if (
                !is_null($contactable) &&
                $self->contactable_type === Location::class
            ) {
                $contactable->city_cache = $self->city;
                $contactable->save();
            }
        });
    }

    /**
     * Create a new instance from an array of address components.
     *
     * @param  array  $parts
     *
     * @return self
     */
    public static function fromArray(array $parts)
    {
        $address = new self;

        // Convert the array of data to an instance of \stdClass.
        $parts = (object) $parts;

        // Ensure we got the required address components.
        $address->validateAddress($parts);

        // We store the components of the address, not the address itself.
        $address->parts = $parts;

        return $address;
    }

    /**
     * Update one or more components of the address.
     *
     * @param  array  $parts
     *
     * @return self
     */
    public function modify($parts)
    {
        foreach ($parts as $part => $value) {
            // Calling the magic method allows us to centralize validation
            // and formatting there instead of duplicating code.
            $this->__set($part, $value);
        }

        return $this;
    }

    /**
     * Helper method to format a street address line from address parts.
     *
     * @param  \stdClass  $parts
     *
     * @return string
     */
    protected function formatStreetAddress(stdClass $parts)
    {
        $streetAddress = ucfirst($parts->street);

        if ($parts->street_number) {
            $streetAddress .= ' '.$parts->street_number;
        }

        if ($this->usePostalFormat && !empty($parts->letter_box)) {
            $streetAddress .= ' bte '.$parts->letter_box;
        }

        return $streetAddress;
    }

    /**
     * Check if a given postal address is valid.
     *
     * @param  array|stdClass  $parts
     *
     * @return void
     *
     * @throws \DomainException if the postal address is invalid.
     */
    protected function validateAddress($parts)
    {
        $requiredParts = ['street', 'postal_code', 'city'];

        foreach ($requiredParts as $part) {
            if (empty($parts->{$part})) {
                throw new DomainException(
                    "Missing [{$part}] component in postal address."
                );
            }
        }

        // The postal code must be composed of exactly four digits.
        // See http://www.upu.int/fileadmin/documentsFiles/activities/addressingUnit/belEn.pdf
        if (!preg_match('#^[0-9]{4}$#', $parts->postal_code)) {
            throw new DomainException(
                "Postal code [{$parts->postal_code}] is invalid."
            );
        }
    }

    /**
     * Get the key-value pairs of data that are specific to this type of contact info.
     *
     * @return array
     */
    protected function getOwnAttributes()
    {
        return [
            'parts' => $this->parts,
        ];
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
            case 'recipient':
            case 'street':
            case 'streetNumber':
            case 'letterBox':
            case 'postalCode':
            case 'city':
            case 'latitude':
            case 'longitude':
            case 'isGeolocatable':
                return $this->parts->{Str::snake($name)} ?? null;
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
            case 'recipient':
            case 'street':
            case 'streetNumber':
            case 'street_number':
            case 'letterBox':
            case 'letter_box':
            case 'postalCode':
            case 'postal_code':
            case 'city':
            case 'latitude':
            case 'longitude':
            case 'isGeolocatable':
            case 'is_geolocatable':
                // Update the value.
                $this->parts->{Str::snake($name)} = $value;

                // Ensure we got the required address components.
                $this->validateAddress($this->parts);
                break;
            default:
                // Fall back on the magic method of the parent.
                parent::__set($name, $value);
                break;
        }
    }

    /**
     * Return a copy of the address which will be
     * formatted for postal mail delivery.
     *
     * @return static
     */
    public function asPostalMail()
    {
        $clone = clone $this;

        $clone->usePostalFormat = true;

        return $clone;
    }

    /**
     * Check if the address has a latitude and a longitude.
     *
     * @return bool
     */
    public function hasGeoCoordinates()
    {
        return ($this->latitude !== null && $this->longitude !== null);
    }

    /**
     * Return the address as a formatted string of text.
     *
     * @return string
     */
    public function toString()
    {
        return $this->__toString();
    }

    /**
     * Convert the address to its string representation.
     *
     * @return string
     */
    public function __toString()
    {
        if ($this->usePostalFormat) {
            return
                $this->recipient."\n".
                $this->formatStreetAddress($this->parts)."\n".
                $this->parts->postal_code.' '.$this->parts->city;
        }

        return $this->formatStreetAddress($this->parts)."\n".$this->city;
    }

    /**
     * Return the address as an HTML string.
     *
     * @return string
     */
    public function toHtml()
    {
        if ($this->usePostalFormat) {
            // Named addresses use the `h-card` microformat.
            // See http://microformats.org/wiki/h-card
            return
                '<div class="h-card" translate="no">'.
                    '<p class="p-name">'.$this->recipient.'</p>'.
                    '<p class="p-adr h-adr">'.
                        '<span class="p-street-address">'.
                            $this->formatStreetAddress($this->parts).
                        '</span><br>'.
                        '<span class="p-postal-code">'.$this->postalCode.'</span> '.
                        '<span class="p-locality">'.$this->city.'</span>'.
                    '</p>'.
                '</div>';
        }

        // ‘Regular’ addresses use the `h-adr` microformat.
        // See http://microformats.org/wiki/h-adr
        return
            '<p class="h-adr" translate="no">'."\n".
            '<span class="p-street-address">'.
                $this->formatStreetAddress($this->parts).
            '</span><br>'."\n".
            '<span class="p-locality">'.$this->city.'</span>'."\n".
            '</p>';
    }
}
