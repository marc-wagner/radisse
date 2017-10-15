<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\SocialNetwork;

class ContactDetailsSocialNetworkTest extends TestCase
{
    /** @test */
    function it_extends_the_contact_details_class()
    {
        $network = new SocialNetwork;

        $this->assertInstanceOf(\App\ContactDetails::class, $network);
    }

    /** @test */
    function can_be_constructed_from_a_url()
    {
        $network = SocialNetwork::fromUrl('https://www.facebook.com/boucheriesanzot');

        $this->assertInstanceOf(SocialNetwork::class, $network);
    }

    /** @test */
    function object_exposes_the_network_name_in_lowercase_as_a_property()
    {
        $network = SocialNetwork::fromUrl('https://www.facebook.com/boucheriesanzot');

        $this->assertSame('facebook', $network->name);
        $this->assertNotSame('Facebook', $network->name);
    }

    /** @test */
    function object_exposes_the_network_handle_as_a_property()
    {
        $network = SocialNetwork::fromUrl('https://www.facebook.com/boucheriesanzot');

        $this->assertSame('boucheriesanzot', $network->handle);
    }

    /**
     * @test
     * @dataProvider facebookAddressProvider
     */
    function it_supports_the_facebook_social_network($url)
    {
        SocialNetwork::fromUrl($url);

        // If we got no exception until here, then everything is fine.
        // This is a workaround for the lack of an annotation that
        // would say we expect no exception to be thrown at all.
        // @see https://github.com/sebastianbergmann/phpunit-documentation/issues/171
        $this->assertTrue(true);
    }

    /** @test */
    function it_detects_facebook_handles()
    {
        $network = SocialNetwork::fromUrl('facebook.com/boucheriesanzot');
        $this->assertSame('boucheriesanzot', $network->handle);

        $network = SocialNetwork::fromUrl('facebook.com/boucherie-sanzot');
        $this->assertSame('boucherie-sanzot', $network->handle);

        $network = SocialNetwork::fromUrl('facebook.com/boucherie.sanzot');
        $this->assertSame('boucherie.sanzot', $network->handle);

        $network = SocialNetwork::fromUrl('facebook.com/boucherie.sanzot-12345');
        $this->assertSame('boucherie.sanzot-12345', $network->handle);
    }

    /**
     * @test
     * @dataProvider twitterAddressProvider
     */
    function it_supports_the_twitter_social_network($url)
    {
        SocialNetwork::fromUrl($url);

        // If we got no exception until here, then everything is fine.
        // This is a workaround for the lack of an annotation that
        // would say we expect no exception to be thrown at all.
        // @see https://github.com/sebastianbergmann/phpunit-documentation/issues/171
        $this->assertTrue(true);
    }

    /** @test */
    function it_detects_twitter_handles()
    {
        $network = SocialNetwork::fromUrl('facebook.com/boucheriesanzot');
        $this->assertSame('boucheriesanzot', $network->handle);

        $network = SocialNetwork::fromUrl('facebook.com/boucherie-sanzot');
        $this->assertSame('boucherie-sanzot', $network->handle);

        $network = SocialNetwork::fromUrl('facebook.com/boucherie.sanzot');
        $this->assertSame('boucherie.sanzot', $network->handle);

        $network = SocialNetwork::fromUrl('facebook.com/boucherie.sanzot-12345');
        $this->assertSame('boucherie.sanzot-12345', $network->handle);
    }

    /**
     * @test
     * @expectedException DomainException
     */
    function it_throws_an_exception_if_the_social_network_cannot_be_recognized()
    {
        SocialNetwork::fromUrl('invalid-social-network-url');
    }

    /** @test */
    function object_exposes_url_as_a_property()
    {
        $network = SocialNetwork::fromUrl('facebook.com/boucheriesanzot');

        $this->assertInternalType('string', $network->url);
        $this->assertSame('https://www.facebook.com/boucheriesanzot', $network->url);
    }

    /** @test */
    function it_is_transformed_to_the_url_when_converted_to_a_string()
    {
        $network = SocialNetwork::fromUrl('facebook.com/boucheriesanzot');

        $this->assertSame(
            'https://www.facebook.com/boucheriesanzot',
            $network->__toString()
        );
        $this->assertSame(
            'https://www.facebook.com/boucheriesanzot',
            (string) $network
        );
    }

    /** @test */
    function it_gathers_the_correct_data_when_saving_the_model_to_the_database()
    {
        // Manually trigger database migrations.
        $this->runDatabaseMigrations();

        // Create and save a SocialNetwork model into the database.
        $network = SocialNetwork::fromUrl('https://www.facebook.com/boucheriesanzot')
            ->withLabel('Page Facebook')
            ->makePublic();
        // Fake data to satisfy database constraints.
        $network->contactable_id = 1;
        $network->contactable_type = 'foo';
        // Save the model.
        $network->save();

        $this->assertDatabaseHas('contact_details', [
            'type' => 'social-network',
            'data' => json_encode([
                'isPublic' => true,
                'label' => 'Page Facebook',
                'name' => 'facebook',
                'handle' => 'boucheriesanzot',
            ]),
        ]);
    }

    /** @test */
    function it_fills_its_properties_when_getting_the_model_from_the_database()
    {
        // Manually trigger database migrations.
        $this->runDatabaseMigrations();

        // Create and save a SocialNetwork model into the database.
        $network = SocialNetwork::fromUrl('https://www.facebook.com/boucheriesanzot')
            ->withLabel('Page Facebook')
            ->makePublic();
        // Fake data to satisfy database constraints.
        $network->contactable_id = 1;
        $network->contactable_type = 'foo';
        // Save the model.
        $network->save();

        // Get the model from the database.
        $network = SocialNetwork::first();

        $this->assertSame('Page Facebook', $network->label);
        $this->assertTrue($network->isPublic);
        $this->assertSame('https://www.facebook.com/boucheriesanzot', $network->url);
    }

    /**
     * We reimplement this method in order to be able to trigger it manually for
     * some specific tests instead of having it being automatically triggered
     * for every test in the file. This allows to make tests run faster.
     */
    protected function runDatabaseMigrations()
    {
        $this->artisan('migrate');

        $this->app[\Illuminate\Contracts\Console\Kernel::class]->setArtisan(null);

        $this->beforeApplicationDestroyed(function () {
            $this->artisan('migrate:rollback');
        });
    }

    /**
     * Provides a series of URLs for the Facebook social network.
     *
     * @return array
     */
    function facebookAddressProvider()
    {
        return [
            ['facebook.com/boucheriesanzot'],
            ['www.facebook.com/boucheriesanzot'],
            ['http://facebook.com/boucheriesanzot'],
            ['http://www.facebook.com/boucheriesanzot'],
            ['https://facebook.com/boucheriesanzot'],
            ['https://www.facebook.com/boucheriesanzot'],
        ];
    }

    /**
     * Provides a series of URLs for the Twitter social network.
     *
     * @return array
     */
    function twitterAddressProvider()
    {
        return [
            ['twitter.com/boucheriesanzot'],
            ['www.twitter.com/boucheriesanzot'],
            ['http://twitter.com/boucheriesanzot'],
            ['http://www.twitter.com/boucheriesanzot'],
            ['https://twitter.com/boucheriesanzot'],
            ['https://www.twitter.com/boucheriesanzot'],
        ];
    }
}
