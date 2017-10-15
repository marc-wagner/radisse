<?php

namespace App;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

/**
 * A partner is a person or organization that uses the local currency.
 */
class Partner extends Model
{
    use HasContactDetails;

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        // When a partner is created, we automatically
        // generate a slug based on its name.
        self::creating(function (self $partner) {
            if (is_null($partner->slug)) {
                $partner->slug = Str::slug($partner->name);
            }
        });
    }

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        // Every time a route explicitly or implicitly expects a partner
        // in one of its segments, it will look for this partner by
        // using its slug instead of the usual primary key.
        //
        // This means that if there is a route like 'foo/{partner}', the
        // partner placeholder will be filled with the slug, not the ID.
        return 'slug';
    }

    /**
     * Get the locations of the partner.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function locations()
    {
        return $this->hasMany(Location::class);
    }

    /**
     * Get the person(s) who represent the partner.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function representatives()
    {
        return $this->hasMany(PartnerRepresentative::class);
    }
}