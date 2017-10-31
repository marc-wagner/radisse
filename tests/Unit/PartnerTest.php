<?php

namespace Tests\Unit\Admin;

use App\Email;
use App\Phone;
use App\Partner;
use App\Location;
use Carbon\Carbon;
use Tests\TestCase;
use App\PostalAddress;
use App\SocialNetwork;
use App\PartnerRepresentative;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;

class PartnerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function can_generate_a_slug_from_the_name_when_creating_a_partner()
    {
        // Create a partner.
        $partner = factory(Partner::class)->create([
            'name' => 'Boucherie Sanzot',
            // Ensure there is no defined slug before creating the model.
            'slug' => null,
        ]);

        // Check that a slug has been properly generated.
        $this->assertSame('boucherie-sanzot', $partner->slug);
    }

    /** @test */
    public function does_not_automatically_generate_a_slug_if_one_is_already_defined()
    {
        // Create a partner.
        $partner = factory(Partner::class)->create([
            'name' => 'Boucherie Sanzot',
            // Ensure there IS a defined slug before creating the model.
            'slug' => 'my-special-slug',
        ]);

        // Check that the slug we provided has been kept as is,
        // that it had not been overwitten by a new one.
        $this->assertSame('my-special-slug', $partner->slug);
        $this->assertNotSame('boucherie-sanzot', $partner->slug);
    }

    /** @test */
    function can_retrieve_its_locations()
    {
        // Create a partner.
        $partner = factory(Partner::class)->create([
            'name' => 'Boucherie Sanzot',
        ]);

        // Then, create two locations for this partner.
        $location1 = factory(Location::class)->create([
            'name' => 'Magasin rue du Nord',
            'partner_id' => $partner->id,
        ]);
        $location2 = factory(Location::class)->create([
            'name' => 'Magasin rue du Sud',
            'partner_id' => $partner->id,
        ]);

        // Retrieve the locations.
        $locations = $partner->locations;

        // Check that we got the correct locations.
        $this->assertCount(2, $locations);

        $this->assertSame($location1->id, $locations[0]->id);
        $this->assertSame('Magasin rue du Nord', $locations[0]->name);

        $this->assertSame($location2->id, $locations[1]->id);
        $this->assertSame('Magasin rue du Sud', $locations[1]->name);
    }

    /** @test */
    function can_retrieve_the_list_of_cities_of_its_locations_in_alphabetical_order()
    {
        // Create a partner.
        $partner = factory(Partner::class)->create();

        // Then, create three locations for this partner,
        // including two in the same city.
        factory(Location::class)->create(['partner_id' => $partner->id])
            ->postalAddress()->save(
                $this->makePostalAddress(['city' => 'Moulinsart'])
            );

        // Duplicated on purpose, to verify that the method
        // under test removes duplicate entries.
        factory(Location::class)->create(['partner_id' => $partner->id])
            ->postalAddress()->save(
                $this->makePostalAddress(['city' => 'Moulinsart'])
            );

        factory(Location::class)->create(['partner_id' => $partner->id])
            ->postalAddress()->save(
                $this->makePostalAddress(['city' => 'Las Dopicos'])
            );

        // Retrieve the list of cities where there are locations.
        $cities = $partner->locationCities();

        // The list must be sorted in alphabetical order
        // and must have no duplicate entries.
        $this->assertSame('Las Dopicos, Moulinsart', $cities);
        $this->assertNotSame('Moulinsart, Las Dopicos', $cities);
    }

    /** @test */
    function can_retrieve_its_representatives()
    {
        $partner = factory(Partner::class)->create([
            'name' => 'Poissonnerie Ordralfabétix',
        ]);

        $personA = factory(PartnerRepresentative::class)->create([
            'partner_id' => $partner->id,
            'given_name' => 'Ordralfabétix',
            'role' => 'gérant',
        ]);
        $personB = factory(PartnerRepresentative::class)->create([
            'partner_id' => $partner->id,
            'given_name' => 'Iélosubmarine',
            'role' => 'gérante',
        ]);

        $representatives = $partner->representatives;

        $this->assertCount(2, $representatives);

        $this->assertSame($personA->id, $representatives[0]->id);
        $this->assertSame('Ordralfabétix', $representatives[0]->given_name);
        $this->assertSame('gérant', $representatives[0]->role);

        $this->assertSame($personB->id, $representatives[1]->id);
        $this->assertSame('Iélosubmarine', $representatives[1]->given_name);
        $this->assertSame('gérante', $representatives[1]->role);
    }

    /** @test */
    function can_retrieve_its_contact_details()
    {
        $partner = factory(Partner::class)->create([
            'name' => 'Boucherie Sanzot',
        ]);

        // Create different types of contact details.
        $address = PostalAddress::fromArray([
            'recipient' => 'Boucherie Sanzot',
            'street' => 'rue du Château',
            'street_number' => '1',
            'letter_box' => null,
            'postal_code' => '1234',
            'city' => 'Moulinsart',
            'latitude' => null,
            'longitude' => null,
        ]);
        $phone = Phone::fromNumber('+32489123456');
        $email = Email::fromAddress('henri@boucheriesanzot.be');
        $network = SocialNetwork::fromUrl('https://www.facebook.com/boucheriesanzot');

        // Save the contact details.
        $partner->postalAddress()->save($address);
        $partner->phones()->save($phone);
        $partner->emails()->save($email);
        $partner->socialNetworks()->save($network);

        // Finally, we test that we can properly get everything back.

        // Postal address.
        $this->assertInstanceOf(PostalAddress::class, $partner->postalAddress);
        $this->assertSame($address->id, $partner->postalAddress->id);

        // Phone numbers.
        $this->assertCount(1, $partner->phones);
        $this->assertInstanceOf(Phone::class, $partner->phones[0]);
        $this->assertSame($phone->id, $partner->phones[0]->id);

        // E-mail addresses.
        $this->assertCount(1, $partner->emails);
        $this->assertInstanceOf(Email::class, $partner->emails[0]);
        $this->assertSame($email->id, $partner->emails[0]->id);

        // Social networks.
        $this->assertCount(1, $partner->socialNetworks);
        $this->assertInstanceOf(SocialNetwork::class, $partner->socialNetworks[0]);
        $this->assertSame($network->id, $partner->socialNetworks[0]->id);
    }

    /** @test */
    function does_not_select_nonvalidated_partners_by_default()
    {
        $validatedPartnerA = factory(Partner::class)->create([
            'validated_at' => Carbon::parse('1 week ago')
        ]);
        $validatedPartnerB = factory(Partner::class)->create([
            'validated_at' => Carbon::parse('1 week ago')
        ]);
        $nonvalidatedPartner = factory(Partner::class)->create([
            'validated_at' => null
        ]);

        $validatedPartners = Partner::all();

        $this->assertTrue($validatedPartners->contains($validatedPartnerA));
        $this->assertTrue($validatedPartners->contains($validatedPartnerB));
        $this->assertFalse($validatedPartners->contains($nonvalidatedPartner));
    }

    /** @test */
    function can_tell_if_it_is_validated_or_not()
    {
        $validatedPartner = factory(Partner::class)->make([
            'validated_at' => Carbon::parse('1 week ago')
        ]);
        $nonvalidatedPartner = factory(Partner::class)->make([
            'validated_at' => null
        ]);

        $this->assertTrue($validatedPartner->isValidated());
        $this->assertFalse($nonvalidatedPartner->isValidated());
    }

    /** @test */
    function can_be_validated()
    {
        $partner = factory(Partner::class)->make([
            'validated_at' => null
        ]);

        $partner->validate();

        $this->assertTrue($partner->isValidated());
    }

    /** @test */
    function can_be_invalidated()
    {
        $partner = factory(Partner::class)->make([
            'validated_at' => Carbon::parse('1 week ago')
        ]);

        $partner->invalidate();

        $this->assertFalse($partner->isValidated());
    }

    /** @test */
    function does_not_select_former_partners_by_default()
    {
        // Create an active partner.
        $activePartner = factory(Partner::class)->create(['name' => 'Boucherie Sanzot']);

        // Create a partner who left the network.
        $formerPartner = factory(Partner::class)->states('former')->create([
            'name' => 'Poissonnerie Ordralfabétix',
            'left_on' => Carbon::parse('3 months ago'),
        ]);

        $partners = Partner::all();

        $this->assertCount(1, $partners);
        $this->assertSame($activePartner->id, $partners->first()->id);
    }

    /**
     * Helper method to make an instance of a postal address.
     *
     * @param  array  $attributes
     *
     * @return \App\PostalAddress
     */
    protected function makePostalAddress($attributes = [])
    {
        $defaultAttributes = [
            'recipient' => 'Boucherie Sanzot',
            'street' => 'rue du Château',
            'street_number' => '1',
            'letter_box' => null,
            'postal_code' => '1234',
            'city' => 'Moulinsart',
            'latitude' => null,
            'longitude' => null,
        ];

        return PostalAddress::fromArray(
            array_merge($defaultAttributes, $attributes)
        );
    }
}
