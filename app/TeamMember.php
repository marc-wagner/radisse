<?php

namespace App;

use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * A team member is a person working for the local currency and who has
 * access to the application in order to be able to do certain things.
 */
class TeamMember extends Authenticatable
{
    use HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'given_name',
        'surname',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        // When a team member is created, we automatically generate
        // a slug based on her given name and surname.
        static::creating(function (self $teamMember) {
            if (is_null($teamMember->slug)) {
                $teamMember->slug = Str::slug(
                    $teamMember->given_name.
                    ' '.
                    $teamMember->surname
                );
            }
        });
    }

    /**
     * Automatically hash the password when it is set.
     *
     * @param string  $value  The non-hashed password
     */
    function setPasswordAttribute($value)
    {
        $this->attributes['password'] = app('hash')->make($value);
    }

    /**
     * Get the team that the person is a member of.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the partners that the team member caused to sign the official documents.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function partners()
    {
        return $this->hasMany(Partner::class, $foreignKey = 'endorser_team_member_id');
    }
}
