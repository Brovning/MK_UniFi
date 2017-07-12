<?
class UniFi extends IPSModule {

    var $ch;
    var $baseURL;
    var $userName;
    var $userPassword;

    public function Create() {
        //Never delete this line!
        parent::Create();

        //These lines are parsed on Symcon Startup or Instance creation
        //You cannot use variables here. Just static values.
        $this->RegisterPropertyString("IPAddress", "https://127.0.0.1:8443");
        $this->RegisterPropertyString("UserName", "admin");
        $this->RegisterPropertyString("UserPassword", "");
        $this->RegisterPropertyString("Clients", "");
        $this->RegisterPropertyInteger("Intervall", 0);
    }

    public function ApplyChanges() {
        //Never delete this line!
        parent::ApplyChanges();

        $this->baseURL = $this->ReadPropertyString("IPAddress");
        $this->userName = $this->ReadPropertyString("UserName");
        $this->userPassword = $this->ReadPropertyString("UserPassword");
        $this->checkInterval = $this->ReadPropertyString("Intervall");

        $this->RegisterVariableString("ClientHTMLBox", "ClientHTMLBox", "~HTMLBox");

        $updateClientsScript = file_get_contents(__DIR__ . "/createClientList.php");
        $updateClientsScriptID = $this->RegisterScript("updateClients", "updateClients", $updateClientsScript);
        IPS_SetScriptTimer($updateClientsScriptID, 60);

        $updateWLANScript = file_get_contents(__DIR__ . "/createWLANList.php");
        $updateWLANScriptID = $this->RegisterScript("updateWLAN", "updateWLAN", $updateWLANScript);
        IPS_SetScriptTimer($updateWLANScriptID, 60);

        $setWLANScript = file_get_contents(__DIR__ . "/setWLAN.php");
        $this->RegisterScript("setWLAN", "setWLAN", $setWLANScript);
    }

    private function Login() {
        $this->baseURL = $this->ReadPropertyString("IPAddress");
        $this->userName = $this->ReadPropertyString("UserName");
        $this->userPassword = $this->ReadPropertyString("UserPassword");

        # init curl object and set session-wide options
        $this->ch = curl_init();


        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($this->ch, CURLOPT_COOKIEFILE, "/tmp/unifi_cookie");
        curl_setopt($this->ch, CURLOPT_COOKIEJAR, "/tmp/unifi_cookie");
        curl_setopt($this->ch, CURLOPT_SSLVERSION, 1); //set TLSv1 (SSLv3 is no longer supported)
        # authenticate against unifi controller
        $url = $this->baseURL . "/api/login";
        $json = "{'username':'" . $this->userName . "', 'password':'" . $this->userPassword . "'}";

        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_POST, 1);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $json);

        curl_exec($this->ch);
    }

    private function Logout() {
        $url = $this->baseURL . "/api/logout";
        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_POST, 0);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, NULL);
        curl_setopt($this->ch, CURLOPT_HTTPGET, TRUE);
        curl_exec($this->ch);

        curl_close($this->ch);
    }

    public function GetClients() {
        $this->Login();

        $url = $this->baseURL . "/api/s/default/stat/sta";

        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_POST, 1);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, "json={}");
        $response = curl_exec($this->ch);

        $this->Logout();

        if ($response !== false) {
            return json_decode($response);
        } else {
            return 0;
        }
    }

    public function GetWLANConfig() {
        $this->Login();

        $url = $this->baseURL . "/api/s/default/list/wlanconf";
        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_POST, 1);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, "json={}");
        $response = curl_exec($this->ch);

        $this->Logout();

        if ($response !== false) {
            return json_decode($response);
        } else {
            return 0;
        }
    }

    public function SetWLANConfig($groupID, $config) {
        $this->Login();

        $url = $this->baseURL . "/api/s/default/upd/wlanconf/" . $groupID;
        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_POST, 1);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, "json=" . json_encode($config) . "");
        curl_exec($this->ch);

        $this->Logout();
    }

}

?>
