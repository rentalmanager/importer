<?php
namespace RentalManager\Importer\FeedProviders;


/**
 * Created by PhpStorm.
 * Date: 7/11/18
 * Time: 2:47 PM
 * Optimizer.php
 * @author Goran Krgovic <goran@dashlocal.com>
 */

class RentLingoOptimizer
{


    /**
     * Run the optimizer
     *
     * @param $file
     * @return string
     */
    public function run($file)
    {
        // output
        $xml_path = $file;

        // load existing XML in a DOMDocument object:
        $src = new \DOMDocument();

        $src->load($xml_path);

        // Use internal errors
        libxml_use_internal_errors(1);

        // Then create the destination DOMDocument object. For this, I use a generic <root> tag; you can replace it with your complete XML wrapping <ad> tags:
        $dom = new \DOMDocument('1.0', 'UTF-8');


        $dom->loadXML( '<ads></ads>' );

        // Format output
        $dom->formatOutput = true;

        $root = $dom->getElementsByTagName( 'ads' )->item(0);

        // Now, init a DOMXpath object for destination XML. DOMXPath permits to execute complex XML queries
        $xpath = new \DOMXPath( $dom );

        // At this point, perform a foreach through all <ad> nodes of source XML and — for each node — retrieve <name> and <area> values:
        foreach( $src->getElementsByTagName( 'ad' ) as $node )
        {
            $name = $node->getElementsByTagName( 'headline' )->item(0)->nodeValue;
            $email = $node->getElementsByTagName( 'email' )->item(0)->nodeValue;
            $area = $node->getElementsByTagName( 'area' )->item(0)->nodeValue;

//     $query =
            $found = $xpath->query( '//ad[email[.="'.$email.'"]]' );
            if($found->length)
            {
                $child = $found->item(0)->getElementsByTagName('units')->item(0);
            }
            else
            {
                $lat  = $node->getElementsByTagName( 'latitude' )->item(0)->nodeValue;
                $long = $node->getElementsByTagName( 'longitude' )->item(0)->nodeValue;
                $address = $node->getElementsByTagName( 'address' )->item(0);


                $address_components = array();
                foreach ( $address->childNodes as $c ) {
                    $address_components[$c->nodeName] = $c->nodeValue;
                }
                unset ( $address_components['#text'] );

                $child = $dom->createElement( 'ad' );

                // Need to create a unique ID for the community
                $community_id = md5($email);

                $child->appendChild( $dom->createElement( 'id', $community_id ) );
                $child->appendChild( $dom->createElement( 'headline', $name ) );
                $child->appendChild( $dom->createElement( 'address', $address_components['line1'] ) );
                $child->appendChild( $dom->createElement( 'address1', $address_components['line2'] ) );
                $child->appendChild( $dom->createElement( 'city', $address_components['city'] ) );
                $child->appendChild( $dom->createElement( 'state', $address_components['province'] ) );
                $child->appendChild( $dom->createElement( 'zip', $address_components['postal'] ) );
                $child->appendChild( $dom->createElement( 'country', $address_components['country'] ) );
                $child->appendChild( $dom->createElement( 'url',  $node->getElementsByTagName( 'url' )->item(0)->nodeValue ) );
                $child->appendChild( $dom->createElement( 'email',  $node->getElementsByTagName( 'email' )->item(0)->nodeValue ) );
                $child->appendChild( $dom->createElement( 'community_name',  $node->getElementsByTagName( 'commName' )->item(0)->nodeValue ) );
                $child->appendChild( $dom->createElement( 'description',  $node->getElementsByTagName( 'description' )->item(0)->nodeValue ) );

                // Amenities
                $amenities = $node->getElementsByTagName( 'amenities' )->item(0)->nodeValue;

                $child->appendChild( $dom->createElement( 'amenities',  $amenities ) );

                $child->appendChild( $dom->createElement( 'latitude', $lat ) );
                $child->appendChild( $dom->createElement( 'longitude', $long ) );

                // Images
                $child->appendChild( $dom->createElement( 'img1',  $node->getElementsByTagName( 'img1' )->item(0)->nodeValue ) );
                $child->appendChild( $dom->createElement( 'img2',  $node->getElementsByTagName( 'img2' )->item(0)->nodeValue ) );
//                $child->appendChild( $dom->createElement( 'img3',  $node->getElementsByTagName( 'img3' )->item(0)->nodeValue ) );

                // Pets
                $child->appendChild( $dom->createElement( 'pets',  $node->getElementsByTagName( 'pets' )->item(0)->nodeValue ) );

                // UNITS
                $child->appendChild( $dom->createElement( 'units' ) );

                $root->appendChild( $child );

                $child = $child->getElementsByTagName('units')->item(0);
            }

            $unit = $dom->createElement( 'unit' );

            $lat  = $node->getElementsByTagName( 'latitude' )->item(0)->nodeValue;
            $long = $node->getElementsByTagName( 'longitude' )->item(0)->nodeValue;

            $unit_id = 'unit_';
            $unit_id .= str_replace('.', '', $lat);
            $unit_id .= str_replace('.', '', $long);
            $unit_id .= round( $node->getElementsByTagName( 'price' )->item(0)->nodeValue  );
            $unit_id .= round( $area  );
            $unit_id .= round(  $node->getElementsByTagName( 'beds' )->item(0)->nodeValue  );
            $unit_id .= round(  $node->getElementsByTagName( 'baths' )->item(0)->nodeValue  );
            $unit_id = md5($unit_id);

            $unit_name = round(  $node->getElementsByTagName( 'beds' )->item(0)->nodeValue  ) . ' Bed, ' . round(  $node->getElementsByTagName( 'baths' )->item(0)->nodeValue  ) . ' Bath';

            $unit->appendChild( $dom->createElement( 'id', $unit_id) );
            $unit->appendChild( $dom->createElement( 'priceMax',  $node->getElementsByTagName( 'priceMax' )->item(0)->nodeValue ) );
            $unit->appendChild( $dom->createElement( 'priceMin',  $node->getElementsByTagName( 'price' )->item(0)->nodeValue ) );
            $unit->appendChild( $dom->createElement( 'name', $unit_name) );
            $unit->appendChild( $dom->createElement( 'sqft',  $area ) );
            $unit->appendChild( $dom->createElement( 'beds',  $node->getElementsByTagName( 'beds' )->item(0)->nodeValue ) );
            $unit->appendChild( $dom->createElement( 'baths',  $node->getElementsByTagName( 'baths' )->item(0)->nodeValue ) );

            $child->appendChild( $unit );

        }

        // save XML as string or file
        $recreated = $dom->saveXML(); // put string in test1
        $path = storage_path('app/feeds/rentlingo/latest-recreated' . '-' . date('Y-m-d_H-i-s') .'.xml');
        $dom->save( $path); // save as file

        // return the path
        return $path;
    }
}
