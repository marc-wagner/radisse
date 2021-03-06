<?php

namespace App;

use DateTime;
use Carbon\CarbonInterval;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Route;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * A partner is a person or organization that uses the local currency.
 */
class Partner extends Model
{
    use HasContactDetails;

    const HEAD_OFFICE_LABEL = 'Siège social';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'name_sort',
        'business_type',
        'is_draft',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'joined_on',
        'left_on',
        'validated_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'endorser_team_member_id' => 'int',
    ];

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $partner) {

            // When a partner is created, we automatically save
            // the ID of the team member who created it.
            if (is_null($partner->creator_team_member_id)) {
                $partner->creator_team_member_id = auth()->id();
            }

            // We also generate a slug based on the partner name.
            if (is_null($partner->slug)) {
                $partner->slug = Str::slug($partner->name);
            }
        });


        // Add some global query scopes if we are *not* in
        // the administration area of the application.
        if (!Route::isAdmin()) {

            // Add a default global scope to all select queries on the model.
            // This will exclude former partners, who left the network of
            // the currency, because most of the time we won’t want them.
            static::addGlobalScope('active', function (Builder $builder) {
                $builder->whereNull('left_on');
            });

            // Add a default global scope to all select queries on the model.
            // This will exclude nonvalidated partners, who have not been
            // accepted (yet) into the network.
            static::addGlobalScope('validated', function (Builder $builder) {
                $builder->whereNotNull('validated_at');
            });

            // Add a default global scope to all select queries on the model.
            // This will exclude ‘incognito’ partners, who do not want to
            // be publicly visible on the website at all.
            static::addGlobalScope('non-incognito', function (Builder $builder) {
                $builder->where('is_incognito', 0);
            });
        }
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
     * Check if the partner has at least one location with a currency exchange.
     *
     * @return bool
     */
    public function hasCurrencyExchange()
    {
        foreach ($this->locations as $location) {
            if ($location->hasCurrencyExchange()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the team that ‘owns’ the partner.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function team()
    {
        return $this->belongsTo(Team::class);
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

    /**
     * Get the team member that made the partner sign the official documents.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function teamMember()
    {
        return $this->belongsTo(
            TeamMember::class,
            $foreignKey = 'endorser_team_member_id'
        );
    }

    /**
     * Get the team member who created this partner.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator()
    {
        return $this->belongsTo(
            TeamMember::class,
            $foreignKey = 'creator_team_member_id'
        );
    }

    /**
     * Get the team member who validated this partner.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function validator()
    {
        return $this->belongsTo(
            TeamMember::class,
            $foreignKey = 'validator_team_member_id'
        );
    }

    /**
     * Check if the partner has been validated.
     *
     * @return bool
     */
    public function isValidated()
    {
        return $this->validated_at instanceof DateTime;
    }

    /**
     * Check if the partner has not been validated.
     *
     * @return bool
     */
    public function isNotValidated()
    {
        return ! $this->isValidated();
    }

    /**
     * Mark the partner as valid from now.
     *
     * @return void
     */
    public function validate()
    {
        $this->validated_at = now();
        $this->save();
    }

    /**
     * Mark the partner as valid and specify who did the validation.
     *
     * @param  \App\TeamMember  $teamMember
     *
     * @return void
     */
    public function validateBy(TeamMember $teamMember)
    {
        $this->validator_team_member_id = $teamMember->id;

        $this->validate();
    }

    /**
     * Invalidate the partner.
     *
     * @return void
     */
    public function invalidate()
    {
        $this->validated_at = null;
        $this->validator_team_member_id = null;
        $this->save();
    }

    /**
     * Scope a query to only include active partners.
     *
     * This will exclude former partners, who
     * left the network of the currency.
     *
     * @param  \Illuminate\Database\Eloquent\Builder
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->whereNull('left_on');
    }

    /**
     * Scope a query to only include former partners.
     *
     * @param  \Illuminate\Database\Eloquent\Builder
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFormer($query)
    {
        return $query->whereNotNull('left_on');
    }

    /**
     * Save a new model as a ‘draft’ and return the instance.
     *
     * @param  array  $attributes
     * @return \Illuminate\Database\Eloquent\Model|$this
     */
    public function createAsDraft(array $attributes = [])
    {
        $attributes['is_draft'] = true;

        return $this->create($attributes);
    }

    public function getHeadOfficeAddress()
    {
        if (
            $this->postalAddress &&
            $this->postalAddress->label === self::HEAD_OFFICE_LABEL
        ) {
            return $this->postalAddress;
        }
    }

    /**
     * Associate or replace a postal address for the head office of the partner.
     *
     * @param array  $parts
     */
    public function setHeadOfficeAddress(array $parts)
    {
        $parts = array_merge(['recipient' => $this->name], $parts);

        if ($address = $this->getHeadOfficeAddress()) {
            // An address already exists, so we’ll update it.
            $address->modify($parts)->save();
        } else {
            // Otherwise we create a new address in the database.
            $address = PostalAddress::fromArray($parts)
                        ->withLabel(self::HEAD_OFFICE_LABEL)
                        ->makePrivate();

            $this->postalAddress()->save($address);
        }
    }

    public function getHeadOfficePhone()
    {
        return $this->findPhoneByLabel(self::HEAD_OFFICE_LABEL);
    }

    public function setHeadOfficePhone($number, $isPublic = false)
    {
        $phone = $this->getHeadOfficePhone();

        if ($phone) {
            $phone->number = $number;
            $phone->isPublic = (bool) $isPublic;
            $phone->save();
        } else {
            $phone = Phone::fromNumber($number)
                        ->withLabel(self::HEAD_OFFICE_LABEL)
                        ->setVisibility($isPublic);

            $this->phones()->save($phone);
        }
    }

    public function removeHeadOfficePhone()
    {
        optional($this->getHeadOfficePhone())->delete();
    }

    public function getHeadOfficeEmail()
    {
        return $this->emails->first(function ($email) {
            return $email->label === 'Siège social';
        });
    }

    public function setHeadOfficeEmail($address, $isPublic = false)
    {
        $email = $this->getHeadOfficeEmail();

        if ($email) {
            $email->address = $address;
            $email->isPublic = (bool) $isPublic;
            $email->save();
        } else {
            $email = Email::fromAddress($address)
                        ->withLabel(self::HEAD_OFFICE_LABEL)
                        ->setVisibility($isPublic);

            $this->emails()->save($email);
        }
    }

    public function removeHeadOfficeEmail()
    {
        optional($this->getHeadOfficeEmail())->delete();
    }

    /**
     * Return the list of cities from the address(es) of the partner’s locations.
     *
     * Cities are sorted in alphabetical order.
     *
     * @return string|null
     */
    public function locationCities()
    {
        $cities = [];

        // Loop on all of the locations of the partner
        // and get the cities they’re in.
        foreach ($this->locations as $location) {

            if (!$location->postalAddress) {
                continue;
            }

            $cities[] = $location->postalAddress->city;
        }

        // Remove duplicates in case there are multiple
        // locations in the same city.
        $cities = array_unique($cities);

        // Then, sort the cities in alphabetical order.
        sort($cities, SORT_LOCALE_STRING);

        return $cities ? implode(', ', $cities) : null;
    }

    /**
     * Check if the partner has been validated recently.
     *
     * @return bool
     */
    public function isRecent($interval = '2 months')
    {
        $interval = CarbonInterval::fromString($interval);

        $start_of_interval = today()->sub($interval);

        // Check if the partner has been validated less than ‘interval’ ago.
        return $this->validated_at > $start_of_interval;
    }
}
