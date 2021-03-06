<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePartnersTable extends Migration
{
    /**
     * Create the table of partners.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('partners', function (Blueprint $table) {

            // Primary key.
            // An unsigned integer with autoincrement.
            $table->increments('id');

            // Optional foreign key.
            // This references the team member who made this partner sign the
            // official documents. This is optional but highly recommended.
            // Note: this just adds the column. The foreign constraint itself
            //       is added via the migration for the `team_members` table.
            $table->unsignedInteger('endorser_team_member_id')->nullable();

            // Optional foreign key.
            // This references the team member who validated the partner.
            // It is supposed to be defined if the `validated_at` column
            // is not null.
            // Note: this just adds the column. The foreign constraint itself
            //       is added via the migration for the `team_members` table.
            $table->unsignedInteger('validator_team_member_id')->nullable();

            // The name of the partner.
            $table->string('name');

            // The name of the partner, but maybe in a modified form that will
            // make it easier for people to find it in an alphabetical list.
            // Example : 'Du côté de chez Poje' → 'Poje (du côté de chez)'
            $table->string('name_sort')->nullable();

            // The slug is a simplified version of the name that is both people-
            // friendly and computer-friendly, for use in places such as URLs
            // or filenames. Each partner must have a different one.
            $table->string('slug')->unique();

            // The type of business (ASBL, SPRL, indépendant en
            // personne physique, etc.)
            $table->string('business_type')->nullable();

            // The dates when the partner entered and, if applicable,
            // when she left the network of the currency.
            $table->date('joined_on')->nullable();
            $table->date('left_on')->nullable();

            // Tells whether or not the partner may be considered ‘complete’.
            $table->boolean('is_draft')->default(false);

            // The date and time at which a partner has been validated.
            // A validated partner is a partner that has been accepted
            // into the network by the relevant people.
            $table->datetime('validated_at')->nullable();

            // Timestamps telling when the table row was created
            // and when it was modified for the last time.
            $table->timestamps();
        });
    }

    /**
     * Drop the table of partners.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('partners');
    }
}
