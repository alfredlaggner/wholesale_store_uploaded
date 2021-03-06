<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Customer_import;
use App\Customer;
use App\Zip_code;

class CustomerUploadController extends Controller
    {
        public function customers_2_shopify()
            {
                ini_set('max_execution_time', 60 * 60); //1 hour
                DB::table('customer_imports')->truncate();
                $customer_import = new Customer_import;
                $customerCounter = 0;

                $customers = Customer::get();

                foreach ($customers as $customer) {
                    $customerCounter++;
                    $customer_import->updateOrCreate($this->CustomerLine($customer));
                }
                $this->export_csv();
                dd('Done with ' . $customerCounter);
                return;
            }

        public function export_csv()
            {
                $csvExporter = new \Laracsv\Export();
                $customer_import = customer_import::get();
                $csvExporter->build($customer_import,
                    [
                        'First Name',
                        'Last Name',
                        'Email',
                        'Company', 'Address1',
                        'Address2',
                        'City',
                        'Province',
                        'Province Code',
                        'Country',
                        'Country Code',
                        'Zip',
                        'Phone',
                        'Accepts Marketing',
                        'Tags',
                        'Note',
                        'Tax Exempt'
                    ]
                )->download("customer_import.csv");
                dd("done");
                return;
            }

        function CustomerLine($customer)
            {
                //   dd($this->getProvince($customer, $customer->zip)) ;
                $imageLine = [
                    'First Name' => $customer->first_name,
                    'Last Name' => $customer->last_name,
                    'Email' => $customer->email,
                    'Company' => $customer->company,
                    'Address1' => $customer->address1,
                    'Address2' => $customer->address2,
                    'City' => $customer->city,
                    'Province Code' => $this->getProvince_short($customer->zip),
                    'Province' => $this->getProvince_long($customer->zip),
                    'Country' => 'United States of America',
                    'Country Code' => 'US',
                    'Zip' => $customer->zip,
                    'Phone' => $customer->phone,
                    'Accepts Marketing' => TRUE,
                    'Tags' => $this->getTags($customer),
                    'Note' => $this->getNote($customer),
                    'Tax Exempt' => FALSE,
                ];
                //          dd($imageLine);
                return $imageLine;
            }


        function getType($customer_id)
            {
                $image = [''];
                $skus = Customer::findOrFail($customer_id);
                foreach ($skus as $sku) {

                }
                return NULL;
            }

        function getTags($customer_id)
            {
                return NULL;
            }

        function getNote($customer)
            {
                //$string = implode(",", $array);

                $guest = $customer->guest ? "yes" : "no";

                $note = "
                Shipping Address:  . 
                $customer->ship_name  . 
                $customer->ship_company . 
                $customer->ship_country . 
                $customer->ship_address1 . 
                $customer->ship_address2 . 
                $customer->ship_city . 
                $customer->ship_zip .
                Created on :  . $customer->date_added . 
                Guest :  . $guest . 
                Credentials:  . 
                $customer->username .
                $customer->password .
                ";

                return $note;
            }


        function getProvince_long($zip)
            {
                $province = '';
                $zips = Zip_code::where("zip", $zip)->get();
                foreach ($zips as $zip) {
                    $province = $zip->full_state;
                }
                return $province;
            }

        function getProvince_short($zip)
            {
                $province = '';
                $zips = Zip_code::where("zip", $zip)->get();
                foreach ($zips as $zip) {
                    $province = $zip->state;
                }
                return $province;

            }

        function get_zip_info($zip, $blnUSA = true)
            {
                $url_string = "https://maps.googleapis.com/maps/api/geocode/json?address=" . $zip . "&key=AIzaSyDpecwyhfIskWywbB5rTgDCGZh4CwpsfBg";
                $url = json_decode(file_get_contents($url_string));
                sleep(1);
                $city = "";
                $state_long = "";
                $state_short = "";
                $state = "";
                $country = "";
                if (count($url->results) > 0) {
                    //break up the components
                    $arrComponents = $url->results[0]->address_components;

                    foreach ($arrComponents as $index => $component) {
                        $type = $component->types[0];

                        if ($city == "" && ($type == "sublocality_level_1" || $type == "locality")) {
                            $city = trim($component->short_name);
                        }
                        if ($state == "" && $type == "administrative_area_level_1") {
                            $state_long = trim($component->long_name);
                            $state_short = trim($component->short_name);

                        }
                        if ($country == "" && $type == "country") {
                            $country = trim($component->short_name);

                            if ($blnUSA && $country != "US") {
                                $city = "";
                                $state = "";
                                break;
                            }
                        }
                        if ($city != "" && $state != "" && $country != "") {
                            //we're done
                            break;
                        }
                    }
                }
                $arrReturn = array("zip" => $zip, "city" => $city, "state_long" => $state_long, "state_short" => $state_short, "country" => $country);
                //             dd($arrReturn);
                return $arrReturn;
            }
    }

                                                                        