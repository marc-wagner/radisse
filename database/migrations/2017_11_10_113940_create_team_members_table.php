<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTeamMembersTable extends Migration
{
    /**
     * Create the table of team members.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('team_members', function (Blueprint $table) {

            // Primary key.
            // An unsigned integer with autoincrement.
            $table->increments('id');

            // Foreign key.
            // This references the team that the person is a member of.
            // Note: this just adds the column. The foreign constraint itself
            //       is added via the migration for the `teams` table.
            $table->unsignedInteger('team_id');

            // These two fields allow to store the identity of the person.
            $table->string('given_name');
            $table->string('surname');

            // The slug is a simplified version of the full name that is both
            // people-friendly and computer-friendly. It is used in places
            // such as URLs. Each team member must have a different one.
            $table->string('slug')->unique();

            // The e-mail address of the person is used as
            // a login in order to access the application.
            $table->string('email')->unique();

            // This stores the hashed (with Bcrypt) password of the person.
            $table->string('password');

            // This is required by the framework because it always tries
            // to update this column when logging people out.
            // See \Illuminate\Auth\SessionGuard::logout()
            $table->rememberToken();

            // Timestamps telling when the table row was created
            // and when it was modified for the last time.
            $table->timestamps();
        });

        // Modify the `partners` table to add nullable
        // foreign keys referencing a team member.
        Schema::table('partners', function (Blueprint $table) {

            // Optional foreign key.
            // This references the team member who made this partner sign the
            // official documents. This is optional but highly recommended.
            $table->foreign('endorser_team_member_id')
                  ->references('id')->on('team_members');

            // Optional foreign key.
            // This references the team member who validated the partner.
            $table->foreign('validator_team_member_id')
                  ->references('id')->on('team_members');
        });
    }

    /**
     * Drop the table of team members.
     *
     * @return void
     */
    public function down()
    {
        // Modify the `partners` table to remove the nullable
        // foreign keys referencing team members.
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('partners', function (Blueprint $table) {
                $table->dropForeign('partners_endorser_team_member_id_foreign');
                $table->dropForeign('partners_validator_team_member_id_foreign');
            });
        }

        // It’s necessary to drop the table only *after* all indexes pointing
        // to it have been removed, otherwise the whole thing crashes because
        // it’s not possible to remove these indexes.
        Schema::dropIfExists('team_members');
    }
}
