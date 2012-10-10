<?php
/*
 * Copyright 2012 - NodePing LLC
 * PHP API testing for NodePing server monitoring service.
 * http://nodeping.com
 *
 * MIT License
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the "Software"), to deal in
 * the Software without restriction, including without limitation the rights to
 * use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies
 * of the Software, and to permit persons to whom the Software is furnished to do
 * so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

// Overview of the NodePing API can be found at https://nodeping.com/API_Documentation
// Full reference is at https://nodeping.com/API_Reference
// Support questions can be sent to support@nodeping.com

include dirname(__FILE__) . "/nodeping.php";
// Create the client object - pass in your NodePing API token.
$test = new NodePingClient(array('token'=>'YOUR TOKEN HERE'));

// Set the ones you wish to test to true.
$testsubaccounts = false;
$testcontacts = false;
$testcontactgroups = false;
$testschedules = false;
$testchecks = false;
$testresults = false;
$testnotifications = false;


echo '<!DOCTYPE html><html lang="en"><head></head>';
// Get a list of your accounts (includes subaccounts)
$accountlist = $test->account->get();
echo "<h2>Account list is: </h2><pre>".print_r($accountlist,true)."</pre>";
// Get parent/primary account info
if($accountlist && is_array($accountlist)){
    $parentid = false;
    if(count($accountlist) == 1){
        $parentid = array_keys($accountlist);
        $parentid =  $parentid[0];
    }else{
        foreach($accountlist as $accountid=>$account){
            if($account['parent']){
                $parentid  =  $accountid;
            }
        }
    }
    if($parentid){
        // Get primary account info
        $myaccount = $test->account->get(array("customerid"=>$parentid));
        echo "<h2>My account info is: </h2><pre>".print_r($myaccount,true)."</pre>";
    }
}

if($testsubaccounts){
    // Create a subaccount
    $subaccountinfo = array(
        "name"=>"Subaccount Test",
        "contactname"=>"John Doe",
        "email"=>"john@example.com", // Valid email for primary contact for this subaccount
        "timezone"=>"-7", // Hour offset for timezone (string)
        "location"=>"nam" // could be "nam", "eur", or "wlw"
    );
    $newsubresponse = $test->account->post($subaccountinfo);
    echo "<h2>New subaccount response is:</h2><pre>".print_r($newsubresponse,true)."</pre>";
    if($newsubresponse['_id']){
        // Get that new subaccount
        $subaccountinfo = $test->account->get(array('customerid'=>$newsubresponse['_id']));
        echo "<h2>Subaccount info is:</h2><pre>".print_r($subaccountinfo,true)."</pre>";
        // Change this subaccount
        $subaccountinfo['timezone'] = '2';
        //$subaccountinfo['customerid'] = $subaccountinfo['_id']; // this is required.
        $modifiedsubaccountresponse = $test->account->put($subaccountinfo);
        echo "<h2>Subaccount update response is:</h2><pre>".print_r($modifiedsubaccountresponse,true)."</pre>";
        // Delete this subaccount
        $deletesubaccountresponse = $test->account->delete(array('customerid'=>$subaccountinfo['_id']));
        echo "<h2>Subaccount delete response is:</h2><pre>".print_r($deletesubaccountresponse,true)."</pre>";
    }
}

if($testcontacts){
    // Get the contacts list
    $contactlist = $test->contact->get();
    echo "<h2>Contact list is: </h2><pre>".print_r($contactlist,true)."</pre>";
    // Add a new contact
    $contactdata = array(
        // "customerid"=>$subaccountid, // Create this contact on a subaccount
        "name"=>"Test Contact", // optional - More of a label than anything else.
        "custrole"=>"edit", // Optional - role permissions, could be "edit", "view", or "notify"
        "newaddresses"=>array(array("address"=>"test@example.com", "type"=>"email"),
                              array("address"=>"@testing", "type"=>"twitter"),
                              array("address"=>"7195551212", "type"=>"sms"), // USA number format
                              array("address"=>"+4043035551212", "type"=>"voice")) // international number format
    );
    $newcontactresponse = $test->contact->post($contactdata);
    echo "<h2>New contact response: </h2><pre>".print_r($newcontactresponse,true)."</pre>";
    // Modify this contact
    if($newcontactresponse && is_array($newcontactresponse) && $newcontactresponse['_id']){
        $newcontactresponse['id'] = $newcontactresponse['_id'];
        // Reset the password
        $resetpasswordresponse = $test->contact->resetpassword(array('id'=>$newcontactresponse['_id']));
        echo "<h2>Resetpassword response: </h2><pre>".print_r($resetpasswordresponse,true)."</pre>";
        // Modify the contact
        foreach($newcontactresponse['addresses'] as $addresskey=>$addressarray){
            if($addressarray['type'] == 'email'){
                $newcontactresponse['addresses'][$addresskey]['address'] = 'newemail@example.com';
            }
        }
        $modifycontactresponse = $test->contact->put($newcontactresponse);
        echo "<h2>Modify contact response: </h2><pre>".print_r($modifycontactresponse,true)."</pre>";
        // Get this contact
        $contact = $test->contact->get(array('id'=>$newcontactresponse['_id']));
        echo "<h2>Contact is: </h2><pre>".print_r($contact,true)."</pre>";
        // Delete this contact
        $contactdeleteresponse = $test->contact->delete(array('id'=>$newcontactresponse['_id']));
        echo "<h2>Contact delete response: </h2><pre>".print_r($contactdeleteresponse,true)."</pre>";
    }
}

if($testcontactgroups){
    // Get the contact group list
    $contactgrouplist = $test->contactgroup->get(); // put "array('customerid'=>$customerid)" as an argument to get the contact groups for a subaccount.
    echo "<h2>Contact group list is: </h2><pre>".print_r($contactgrouplist,true)."</pre>";
    // Add a new contact group
    $contactgroupdata = array(
        // "customerid"=>$subaccountid, // Create this contact group on a subaccount
        "name"=>"Test Contact Group", // optional - More of a label than anything else.
        "members"=>array("Y59XV", "4AWPK") // contact ids - must be on the same subaccount
    );
    $newcontactgroupresponse = $test->contactgroup->post($contactgroupdata);
    echo "<h2>New contact group response: </h2><pre>".print_r($newcontactgroupresponse,true)."</pre>";
    // Modify this contact group
    if($newcontactgroupresponse && is_array($newcontactgroupresponse) && $newcontactgroupresponse['_id']){
        $newcontactgroupresponse['id'] = $newcontactgroupresponse['_id'];
        // Remove a contact from the group
        unset($newcontactgroupresponse['members'][0]);
        // Add a contact to the group
        $newcontactgroupresponse['members'][] = 'X56BA';
        $modifycontactgroupresponse = $test->contactgroup->put($newcontactgroupresponse);
        echo "<h2>Modify contact group response: </h2><pre>".print_r($modifycontactgroupresponse,true)."</pre>";
        // Get this contact group
        $contactgroup = $test->contactgroup->get(array('id'=>$newcontactgroupresponse['_id']));
        echo "<h2>Contact group is: </h2><pre>".print_r($contactgroup,true)."</pre>";
        // Delete this contact group
        $contactgroupdeleteresponse = $test->contactgroup->delete(array('id'=>$newcontactgroupresponse['_id']));
        echo "<h2>Contact group delete response: </h2><pre>".print_r($contactgroupdeleteresponse,true)."</pre>";
    }
}

if($testschedules){
    // Get the schedules list
    $schedulelist = $test->schedule->get(); // put "array('customerid'=>$customerid)" as an argument to get the schedules for a subaccount.
    echo "<h2>Schedule list is: </h2><pre>".print_r($schedulelist,true)."</pre>";
    // Add a new schedule
    $scheduledata = array(
        'id'=> 'All Mornings',
        'data'=> array(
            "monday"=>array(
                "time1"=> "1:00",
                "time2"=> "8:00",
                "exclude"=> 0
            ),
            "tuesday"=>array(
                "time1"=> "1:00",
                "time2"=> "8:00",
                "exclude"=> 0
            ),
            "wednesday"=>array(
                "time1"=> "1:00",
                "time2"=> "8:00",
                "exclude"=> 0
            ),
            "thursday"=>array(
                "time1"=> "1:00",
                "time2"=> "8:00",
                "exclude"=> 0
            ),
            "friday"=>array(
                "time1"=> "1:00",
                "time2"=> "8:00",
                "exclude"=> 0
            ),
            "saturday"=>array(
                "time1"=> "1:00",
                "time2"=> "8:00",
                "exclude"=> 0
            ),
            "sunday"=>array(
                "time1"=> "1:00",
                "time2"=> "8:00",
                "exclude"=> 0
            )
        )
    );
    $newscheduleresponse = $test->schedule->put($scheduledata);
    echo "<h2>New schedule response: </h2><pre>".print_r($newscheduleresponse,true)."</pre>";
    // Get this new schedule by id
    if($newscheduleresponse && $newscheduleresponse['id']){
        $schedule = $test->schedule->get(array('id'=>$scheduledata['id']));
        echo "<h2>Schedule is: </h2><pre>".print_r($schedule,true)."</pre>";
        $modifiedschedule = array('id'=>$scheduledata['id'],
                                  'data'=>$schedule);
        // no Monday's please!
        $modifiedschedule['data']['monday']['exclude'] = 1;
        $modifiedscheduleresponse = $test->schedule->put($modifiedschedule);
        echo "<h2>Modified schedule response: </h2><pre>".print_r($modifiedscheduleresponse,true)."</pre>";
        $schedule = $test->schedule->get(array('id'=>$scheduledata['id']));
        echo "<h2>Modified schedule is: </h2><pre>".print_r($schedule,true)."</pre>";
        // Delete this schedule
        $scheduledeleteresponse = $test->schedule->delete(array('id'=>$scheduledata['id']));
        echo "<h2>Schedule delete response: </h2><pre>".print_r($scheduledeleteresponse,true)."</pre>";
    }
}

if($testchecks){
    // Get the checks list
    $checklist = $test->check->get(); // put "array('customerid'=>$customerid)" as an argument to get the checks for a subaccount.
    echo "<h2>Check list is: </h2><pre>".print_r($checklist,true)."</pre>";
    // Add a new check
    $checkdata = array(
        // "customerid"=>$subaccountid, // Create this check on a subaccount
        "type"=>"HTTP",
        "target"=>"http://nodeping.com",
        "label"=>"Test Check",
        "interval"=>3,
        "threshold"=>5,
        "enabled"=>"true", // Set to 'true' to enable check.  Note: these must be strings
        "sens"=>3,
        "notifications"=>array("Y59XV"=>"All", "4AWPK"=>"Days") // contact ids and schedules - must be on the same subaccount
    );
    $newcheckresponse = $test->check->post($checkdata);
    echo "<h2>New check response: </h2><pre>".print_r($newcheckresponse,true)."</pre>";
    if($newcheckresponse && is_array($newcheckresponse) && $newcheckresponse['_id']){
        // Get this check
        $check = $test->check->get(array("id"=>$newcheckresponse['_id']));
        echo "<h2>Check is: </h2><pre>".print_r($check,true)."</pre>";
        // modify this check - only send the parts you want to modify.
        $checkmodification = array('id' => $check['_id']);
        $checkmodification['target'] = 'https://api.nodeping.com';
        $checkmodification['enabled'] = 'false'; // Note the string representation of boolean
        $modifycheckresponse = $test->check->put($checkmodification);
        echo "<h2>Modify check response: </h2><pre>".print_r($modifycheckresponse,true)."</pre>";
        // Get this check
        $check = $test->check->get(array("id"=>$newcheckresponse['_id']));
        echo "<h2>Modified check is: </h2><pre>".print_r($check,true)."</pre>";
        // Delete this check
        $checkdeleteresponse = $test->check->delete(array("id"=>$newcheckresponse['_id']));
        echo "<h2>Check delete response: </h2><pre>".print_r($checkdeleteresponse,true)."</pre>";
    }
}

if($testresults){
    // Get the results list
    $resultslist = $test->result->get(array('id'=>'201205102227VZ6XU-71U2P95U',
                                            //'customerid'=>$subaccountid, // to get results for a check on a subaccount.
                                            'span'=>3, // 3 hours worth of results
                                            'limit'=>100, // Last 100 results (the smaller of span/limit is used)
                                            //'start'=>'2012-10-01 00:00:00', // Start time
                                            //'end'=>'2012-10-11 00:00:00', // End time
                                            'clean'=>true
                                           ));
    echo "<h2>Results list is: </h2><pre>".print_r($resultslist,true)."</pre>";
    // Get all checks that are currently failing
    $failinglist = $test->result->current();
    echo "<h2>Failing list is: </h2><pre>".print_r($failinglist,true)."</pre>";
}

if($testnotifications){
    // Get the notifications list
    $notifiationlist = $test->notification->get(array('id'=>'201205102227VZ6XU-ZNS45FJA',// no id will get all recent notifications for all checks on the account
                                            //'customerid'=>$subaccountid, // to get results for a check on a subaccount.
                                            //'span'=>300, // 3 hours worth of results
                                            'limit'=>100, // Last 100 results (the smaller of span/limit is used)
                                           ));
    echo "<h2>Notification list is: </h2><pre>".print_r($notifiationlist,true)."</pre>";
}
echo '</html>';
exit;