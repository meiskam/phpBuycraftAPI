<?php
/**
 * phpBuycraftAPI
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Class for communicating easily with the buycraft.net API
 * Currently in 'read-only' mode, none of the actions which delete remote data have been implemented
 * 
 * @author meiskam <meiskam@gmail.com>
 * @version 3.0.1
 * @copyright 2013 meiskam / ShiniNet (http://www.shininet.org)
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */
class phpBuycraftAPI {
  private $baseUrl = "https://api.buycraft.net/v3?";
  private $secret = "unspecified";
  private $secretBad = NULL;
  private $dataStore;

  /**
   * Constructor, make a new instance and initialize with Secret API key
   * 
   * @param string $secret Secret API key
   * @throws \RuntimeException
   */
  public function __construct($secret=NULL) {
    if ($secret === NULL) {
      throw new \RuntimeException("Error: No Secret key input.\n");
    } else {
      $this->secret = $secret;
    }
    $this->dataStore = new phpBuycraftData();
  }

  /**
   * Fetches a formed API request
   * 
   * @param array $indata [key=>string, value=>string] Used for building http query
   * @return array|NULL parsed response
   * @throws \RuntimeException
   */
  public function fetch($indata) {
    $data = array_merge(array("secret"=>$this->secret), $indata);
    $raw = file_get_contents($this->baseUrl.http_build_query($data));
    $json = json_decode($raw);
    $code = $json->{"code"};
    if ($code === phpBuycraftStatus::$OK[0]) {
      return $json;
    }
    ob_start();
    if ($code === phpBuycraftStatus::$NEED_MORE_INFO[0]) {
      echo "Error: ".phpBuycraftStatus::$NEED_MORE_INFO[1]."\n";
    } else if ($code === phpBuycraftStatus::$SECRET_NOT_FOUND[0]) {
      $this->secretBad = TRUE;
      echo "Error: ".phpBuycraftStatus::$SECRET_NOT_FOUND[1]."\n";
    } else if ($code === phpBuycraftStatus::$UNKNOWN_ACTION[0]) {
      echo "Error: ".phpBuycraftStatus::$UNKNOWN_ACTION[1]."\n";
    } else {
      echo "Error: The API returned an unknown code: ".$code.".\n";
    }
    var_dump($indata); //DEBUG
    var_dump($json); //DEBUG
    throw new \RuntimeException(ob_get_clean());
    return NULL;
  }

  /**
   * Issues 'info' request, stores response in data store
   * This command will force update stale cached data
   * 
   * @return bool TRUE if data successfully added response to data store
   */
  public function refreshInfo() {
    $data = array("action"=>"info");
    $return = $this->fetch($data);
    if ($return === NULL) { return FALSE; }
    $payload = $return->{"payload"};
    $this->dataStore->info = $payload;
    return TRUE;
  }
  /**
   * Issues 'packages' request, stores response in data store
   * This command will force update stale cached data
   * 
   * @return bool TRUE if data successfully added response to data store
   */
  public function refreshPackages() {
    $data = array("action"=>"packages");
    $return = $this->fetch($data);
    if ($return === NULL) { return FALSE; }
    $payload = $return->{"payload"};
    $this->dataStore->packages = $payload;
    return TRUE;
  }
  /**
   * Issues 'payments' request, stores response in data store
   * This command will force update stale cached data
   * 
   * @return bool TRUE if data successfully added response to data store
   */
  public function refreshPayments() {
    $data = array("action"=>"payments");
    $return = $this->fetch($data);
    if ($return === NULL) { return FALSE; }
    $payload = $return->{"payload"};
    $this->dataStore->payments = $payload;
    return TRUE;
  }
  /**
   * Issues 'commands' request, stores response in data store
   * This command will force update stale cached data
   * 
   * @return bool TRUE if data successfully added response to data store
   */
  public function refreshCommands() {
    $data = array("action"=>"commands", "do"=>"lookup");
    $return = $this->fetch($data);
    if ($return === NULL) { return FALSE; }
    $payload = $return->{"payload"};
    $this->dataStore->commands = $payload->{"commands"};
    return TRUE;
  }
  //private function commandsRemove() {} //TODO
  /**
   * Issues 'checker' request, stores response in data store
   * This command will force update stale cached data
   * 
   * @return bool TRUE if data successfully added response to data store
   */
  public function refreshChecker() {
    $data = array("action"=>"checker", "do"=>"lookup");
    $return = $this->fetch($data);
    if ($return === NULL) { return FALSE; }
    $payload = $return->{"payload"};
    $this->dataStore->checker = $payload;
    return TRUE;
  }
  //private function checkerRemove() {} //TODO

  /**
   * Issues 'info' request if it's not already in the data store
   * This command is not required, data is automatically fetched as needed
   * 
   * @return bool TRUE if data successfully added response to data store
   */
  public function ensureInfo() {
    if ($this->secretBad === TRUE) { return FALSE; }
    if ($this->dataStore->info == NULL) { return $this->refreshInfo(); }
    return TRUE;
  }
  /**
   * Issues 'packages' request if it's not already in the data store
   * This command is not required, data is automatically fetched as needed
   * 
   * @return bool TRUE if data successfully added response to data store
   */
  public function ensurePackages() {
    if ($this->secretBad === TRUE) { return FALSE; }
    if ($this->dataStore->packages == NULL) { return $this->refreshPackages(); }
    return TRUE;
  }
  /**
   * Issues 'payments' request if it's not already in the data store
   * This command is not required, data is automatically fetched as needed
   * 
   * @return bool TRUE if data successfully added response to data store
   */
  public function ensurePayments() {
    if ($this->secretBad === TRUE) { return FALSE; }
    if ($this->dataStore->payments == NULL) { return $this->refreshPayments(); }
    return TRUE;
  }
  /**
   * Issues 'commands' request if it's not already in the data store
   * This command is not required, data is automatically fetched as needed
   * 
   * @return bool TRUE if data successfully added response to data store
   */
  public function ensureCommands() {
    if ($this->secretBad === TRUE) { return FALSE; }
    if ($this->dataStore->commands == NULL) { return $this->refreshCommands(); }
    return TRUE;
  }
  /**
   * Issues 'checker' request if it's not already in the data store
   * This command is not required, data is automatically fetched as needed
   * 
   * @return bool TRUE if data successfully added response to data store
   */
  public function ensureChecker() {
    if ($this->secretBad === TRUE) { return FALSE; }
    if ($this->dataStore->checker == NULL) { return $this->refreshChecker(); }
    return TRUE;
  }

  /**
   * @ignore
   * @return stdClass|NULL
   */
  public function rawInfo() {
    if ($this->ensureInfo() === FALSE) { return NULL; }
    return $this->dataStore->info;
  }
  /**
   * @ignore
   * @return array|NULL [int=>$key, stdClass=>$value]
   */
  public function rawPackages() {
    if ($this->ensurePackages() === FALSE) { return NULL; }
    return $this->dataStore->packages;
  }
  /**
   * @ignore
   * @return array|NULL [int=>$key, stdClass=>$value]
   */
  public function rawPayments() {
    if ($this->ensurePayments() === FALSE) { return NULL; }
    return $this->dataStore->payments;
  }
  /**
   * @ignore
   * @return array|NULL [int=>$key, stdClass=>$value]
   */
  public function rawCommands() {
    if ($this->ensureCommands() === FALSE) { return NULL; }
    return $this->dataStore->commands;
  }
  /**
   * @ignore
   * @return stdClass|NULL
   */
  public function rawChecker() {
    if ($this->ensureChecker() === FALSE) { return NULL; }
    return $this->dataStore->checker;
  }

  /**
   * Get latest Bukkit plugin version
   * 
   * @return float|NULL version number
   */
  public function getLatestVersion() {
    if ($this->ensureInfo() === FALSE) { return NULL; }
    return $this->dataStore->info->{"latestVersion"};
  }
  /**
   * Get link to latest Bukkit plugin download
   * 
   * @return string|NULL download url
   */
  public function getLatestDownload() {
    if ($this->ensureInfo() === FALSE) { return NULL; }
    return $this->dataStore->info->{"latestDownload"};
  }
  /**
   * Get server identification number
   * 
   * @return int|NULL server id
   */
  public function getServerId() {
    if ($this->ensureInfo() === FALSE) { return NULL; }
    return $this->dataStore->info->{"serverId"};
  }
  /**
   * Get default donation currency
   * 
   * @return string|NULL currency name
   */
  public function getServerCurrency() {
    if ($this->ensureInfo() === FALSE) { return NULL; }
    return $this->dataStore->info->{"serverCurrency"};
  }
  /**
   * Get Bukkit server name
   * 
   * @return string|NULL server name
   */
  public function getServerName() {
    if ($this->ensureInfo() === FALSE) { return NULL; }
    return $this->dataStore->info->{"serverName"};
  }
  /**
   * Get link to server's Buycraft store
   * 
   * @return string|NULL store url
   */
  public function getServerStore() {
    if ($this->ensureInfo() === FALSE) { return NULL; }
    return $this->dataStore->info->{"serverStore"};
  }

  /**
   * Get quantity of available packages
   * 
   * @return int|NULL package count
   */
  public function getPackageCount() {
    if ($this->ensurePackages() === FALSE) { return NULL; }
    return count($this->dataStore->packages);
  }
  /**
   * Get remote package id
   * @param int $num local package number
   * 
   * @return int|NULL package id
   */
  public function getPackageId($num=-1) {
    if ($num <= -1 || $this->ensurePackages() === FALSE || $num >= count($this->dataStore->packages)) { return NULL; }
    return $this->dataStore->packages[$num]->{"id"};
  }
  /**
   * Get package display order
   * 
   * @param int $num local package number
   * @return int|NULL order
   */
  public function getPackageOrder($num=-1) {
    if ($num <= -1 || $this->ensurePackages() === FALSE || $num >= count($this->dataStore->packages)) { return NULL; }
    return $this->dataStore->packages[$num]->{"order"};
  }
  /**
   * Get package name
   * 
   * @param int $num local package number
   * @return string|NULL name
   */
  public function getPackageName($num=-1) {
    if ($num <= -1 || $this->ensurePackages() === FALSE || $num >= count($this->dataStore->packages)) { return NULL; }
    return $this->dataStore->packages[$num]->{"name"};
  }
  /**
   * Get package description
   * 
   * @param int $num local package number
   * @return string|NULL description
   */
  public function getPackageDescription($num=-1) {
    if ($num <= -1 || $this->ensurePackages() === FALSE || $num >= count($this->dataStore->packages)) { return NULL; }
    return $this->dataStore->packages[$num]->{"description"};
  }
  /**
   * Get package price
   * 
   * @param int $num local package number
   * @return string|NULL price
   */
  public function getPackagePrice($num=-1) {
    if ($num <= -1 || $this->ensurePackages() === FALSE || $num >= count($this->dataStore->packages)) { return NULL; }
    return $this->dataStore->packages[$num]->{"price"};
  }

  /**
   * Get total quantity of payments
   * 
   * @return int|NULL payment count
   */
  public function getPaymentCount() {
    if ($this->ensurePayments() === FALSE) { return NULL; }
    return count($this->dataStore->payments);
  }
  /**
   * Get time of payment
   * 
   * @param int $num local payment number
   * @return int|NULL time in seconds since epoch
   */
  public function getPaymentTime($num=-1) {
    if ($num <= -1 || $this->ensurePayments() === FALSE || $num >= count($this->dataStore->payments)) { return NULL; }
    return $this->dataStore->payments[$num]->{"time"};
  }
  /**
   * Get packages included in payment
   * 
   * @param int $num local payment number
   * @return array|NULL [int=>$key, int=>$value] remote id number of packages
   */
  public function getPaymentPackages($num=-1) {
    if ($num <= -1 || $this->ensurePayments() === FALSE || $num >= count($this->dataStore->payments)) { return NULL; }
    return $this->dataStore->payments[$num]->{"packages"};
  }
  /**
   * Get name of player
   * 
   * @param int $num local payment number
   * @return string|NULL in-game nickname of player
   */
  public function getPaymentPlayerName($num=-1) {
    if ($num <= -1 || $this->ensurePayments() === FALSE || $num >= count($this->dataStore->payments)) { return NULL; }
    return $this->dataStore->payments[$num]->{"ign"};
  }
  /**
   * Get payment price
   * 
   * @param int $num local payment number
   * @return string|NULL amount of money, to 2 decimal places
   */
  public function getPaymentPrice($num=-1) {
    if ($num <= -1 || $this->ensurePayments() === FALSE || $num >= count($this->dataStore->payments)) { return NULL; }
    return $this->dataStore->payments[$num]->{"price"};
  }
  /**
   * Get currency used in transaction
   * 
   * @param int $num local payment number
   * @return string|NULL currency name
   */
  public function getPaymentCurrency($num=-1) {
    if ($num <= -1 || $this->ensurePayments() === FALSE || $num >= count($this->dataStore->payments)) { return NULL; }
    return $this->dataStore->payments[$num]->{"currency"};
  }

  /**
   * Get quantity of commands waiting to be issued on server
   * 
   * @return int|NULL number of commands
   */
  public function getCommandCount() {
    if ($this->ensureCommands() === FALSE) { return NULL; }
    return count($this->dataStore->commands);
  }
  /**
   * Get in-game player name of command target
   * 
   * @param int $num local command number
   * @return string|NULL player name
   */
  public function getCommandPlayerName($num=-1) {
    if ($num <= -1 || $this->ensureCommands() === FALSE || $num >= count($this->dataStore->commands)) { return NULL; }
    return $this->dataStore->commands[$num]->{"ign"};
  }
  /**
   * Get list of commands to be executed
   * 
   * @param int $num local command number
   * @return array|NULL [int=>$key, string=>$value]
   */
  public function getCommandList($num=-1) {
    if ($num <= -1 || $this->ensureCommands() === FALSE || $num >= count($this->dataStore->commands)) { return NULL; }
    return $this->dataStore->commands[$num]->{"commands"};
  }
  /**
   * Get require online status
   * 
   * @param int $num local command number
   * @return bool|NULL TRUE if player must in-game before command is executed
   */
  public function getCommandRequireOnline($num=-1) {
    if ($num <= -1 || $this->ensureCommands() === FALSE || $num >= count($this->dataStore->commands)) { return NULL; }
    return $this->dataStore->commands[$num]->{"requireOnline"};
  }

  /**
   * Get quantity of commands waiting to be issued on server
   * 
   * @return int|NULL number of commands
   */
  public function getClaimableCount() {
    if ($this->ensureChecker() === FALSE) { return NULL; }
    return count($this->dataStore->checker->{"claimables"});
  }
  /**
   * Get in-game player name of command target
   * 
   * @param int $num local command number
   * @return string|NULL player name
   */
  public function getClaimablePlayerName($num=-1) {
    if ($num <= -1 || $this->ensureChecker() === FALSE || $num >= count($this->dataStore->checker->{"claimables"})) { return NULL; }
    return $this->dataStore->checker->{"claimables"}[$num]->{"ign"};
  }
  /**
   * Get list of commands to be executed
   * 
   * @param int $num local command number
   * @return array|NULL [int=>$key, string=>$value]
   */
  public function getClaimableCommandList($num=-1) {
    if ($num <= -1 || $this->ensureChecker() === FALSE || $num >= count($this->dataStore->checker->{"claimables"})) { return NULL; }
    return $this->dataStore->checker->{"claimables"}[$num]->{"commands"};
  }
  /**
   * Get require online status
   * 
   * @param int $num local command number
   * @return bool|NULL TRUE if player must in-game before command is executed
   */
  public function getClaimableRequireOnline($num=-1) {
    if ($num <= -1 || $this->ensureChecker() === FALSE || $num >= count($this->dataStore->checker->{"claimables"})) { return NULL; }
    return $this->dataStore->checker->{"claimables"}[$num]->{"requireOnline"};
  }

  /**
   * Get quantity of commands waiting to be issued on server because a package has expired
   * 
   * @return int|NULL number of commands
   */
  public function getExpiryCount() {
    if ($this->ensureChecker() === FALSE) { return NULL; }
    return count($this->dataStore->checker->{"expirys"});
  }
  /**
   * Get in-game player name of command target
   * 
   * @param int $num local command number
   * @return string|NULL player name
   */
  public function getExpiryPlayerName($num=-1) {
    if ($num <= -1 || $this->ensureChecker() === FALSE || $num >= count($this->dataStore->checker->{"expirys"})) { return NULL; }
    return $this->dataStore->checker->{"expirys"}[$num]->{"ign"};
  }
  /**
   * Get list of commands to be executed
   * 
   * @param int $num local command number
   * @return array|NULL [int=>$key, string=>$value]
   */
  public function getExpiryCommandList($num=-1) {
    if ($num <= -1 || $this->ensureChecker() === FALSE || $num >= count($this->dataStore->checker->{"expirys"})) { return NULL; }
    return $this->dataStore->checker->{"expirys"}[$num]->{"commands"};
  }
  /**
   * Get require online status
   * 
   * @param int $num local command number
   * @return bool|NULL TRUE if player must in-game before command is executed
   */
  public function getExpiryRequireOnline($num=-1) {
    if ($num <= -1 || $this->ensureChecker() === FALSE || $num >= count($this->dataStore->checker->{"expirys"})) { return NULL; }
    return $this->dataStore->checker->{"expirys"}[$num]->{"requireOnline"};
  }

  /**
   * Get local package numbers from a remote package id
   * 
   * @param int $id remote package id
   * @return array|NULL [int=>$key, int=>$num] local package numbers
   */
  public function getPackagesById($id=-1) {
    if ($id <= -1 || $this->ensurePackages() === FALSE) { return NULL; }
    $output = array();
    $max = count($this->dataStore->packages);
    for($i = 0; $i < $max; $i++) {
      if ($this->dataStore->packages[$i]->{"id"} === $id) {
        $output[] = $i;
      }
    }
    return $output;
  }
  /**
   * Get local payment numbers newer than specified time
   * 
   * @param int $time time in seconds since epoch
   * @return array|NULL [int=>$key, int=>$num] local payment numbers
   */
  public function getPaymentsSince($time=-1) {
    if ($time <= -1 || $this->ensurePayments() === FALSE) { return NULL; }
    $output = array();
    $max = count($this->dataStore->payments);
    for($i = $max-1; $i >= 0; $i--) {
      if ($this->dataStore->payments[$i]->{"time"} >= $time) {
        $output[] = $i;
      } else {
        break;
      }
    }
    return $output;
  }
  /**
   * Get specified newest payment numbers
   * 
   * @param int $amount quantity of payments
   * @return array|NULL [int=>$key, int=>$num] local payment numbers
   */
  public function getRecentPayments($amount=-1) {
    if ($amount <= 0 || $this->ensurePayments() === FALSE) { return NULL; }
    $output = array();
    $max = count($this->dataStore->payments);
    for($i = $max-1; $i > $max-1-$amount && $i >= 0; $i--) {
      $output[] = $i;
    }
    return $output;
  }
  /**
   * Get local payment numbers created by specified player name (case-insensitive)
   * 
   * @param string $playerName in-game player name
   * @return array|NULL [int=>$key, int=>$num] local payment numbers
   */
  public function getPaymentsByPlayerName($playerName=NULL) {
    if ($num === NULL || $this->ensurePayments() === FALSE) { return NULL; }
    $output = array();
    $max = count($this->dataStore->payments);
    for($i = 0; $i < $max; $i++) {
      if (strcasecmp($this->dataStore->payments[$i]->{"ign"}, $playerName) === 0) {
        $output[] = $i;
      }
    }
    return $output;
  }
  /**
   * Get local payment numbers which involved specified remote package id
   * 
   * @param int $id remote package id
   * @return array|NULL [int=>$key, int=>$num] local payment numbers
   */
  public function getPaymentsByPackageId($id=-1) {
    if ($id <= -1 || $this->ensurePayments() === FALSE) { return NULL; }
    $output = array();
    $max = count($this->dataStore->payments);
    for($i = 0; $i < $max; $i++) {
      $max2 = count($this->dataStore->payments[$i]->{"packages"});
      for ($j = 0; $j < $max2; $j++) {
        if ($this->dataStore->payments[$i]->{"packages"}[$j] === $id) {
          $output[] = $i;
        }
      }
    }
    return $output;
  }

  /**
   * Get a link to add a package to users cart
   * 
   * @param int $id remote package id number
   * @param string $name OPTIONAL in-game name of player making payment
   * @return string|NULL URL to add package to buycraft.net cart
   */
  public function getBuyLink($id=-1, $name=NULL) {
    $store = $this->getServerStore();
    if ($id <= -1 || $store === NULL) { return NULL; }
    $data = array("action"=>"add", "package"=>$id);
    if ($name !== NULL) { $data["ign"] = $name; }
    return $store."/checkout/packages?".http_build_query($data);
  }
  /**
   * Get a link to directly make a payment
   * 
   * @param int $id remote package id number
   * @param string $name in-game name of player making payment
   * @param string $gateway payment gateway
   * @return string|NULL URL to buy package directly from gateway
   */
  public function getBuyLinkDirect($id=-1, $name=NULL, $gateway=NULL) {
    $store = $this->getServerStore();
    if ($id <= -1 || $name === NULL || $store === NULL || $gateway === NULL) { return NULL; }
    $data = array("direct"=>"true", "package"=>$id, "agreement"=>"true", "gateway"=>$gateway, "ign"=>$name);
    return $store."/checkout/pay?".http_build_query($data);
  }
}

/**
 * Status codes returned by the buycraft.net API
 * 
 * @author meiskam <meiskam@gmail.com>
 */
class phpBuycraftStatus {
  public static $OK = array(0, "Authenticated with the specified Secret key.");
  public static $NEED_MORE_INFO = array(100, "The specified action requires more information then was provided.");
  public static $SECRET_NOT_FOUND = array(101, "The specified Secret key could not be found.");
  public static $UNKNOWN_ACTION = array(102, "The specified action does not exist.");
}

/**
 * Data store for {@see phpBuycraftAPI}
 * 
 * @author meiskam <meiskam@gmail.com>
 */
class phpBuycraftData {
  public $info = NULL;
  public $packages = NULL;
  public $payments = NULL;
  public $commands = NULL;
  public $checker = NULL;
}
?>