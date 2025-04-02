<?php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

class ApiClient {

    private $base_url;
    private $insMainModel;

    public function __construct($base_url) {
        $this->base_url = $base_url;
        $this->insMainModel = new mainModel();
    }

    public function sendSmsClaro($msisdn, $message, $proveedor_id, $api_key) {
        $url = "{$this->base_url}/send_to_contact";
        $id =  $this->insMainModel->correlativo("sms_id", "sms");

        $params = [
            'msisdn' => $msisdn,
            'message' => $message,
            'id' => $id,
            'api_key' => $api_key,
        ];

        $url .= '?' . http_build_query($params);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Agregar la siguiente línea para desactivar la verificación SSL
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            echo 'Error en la solicitud cURL: ' . curl_error($ch);
        }

        curl_close($ch);

        $responseData = json_decode($response, true);
        $response = "";

        if ($responseData !== null && isset($responseData['sms_sent']) && $responseData['sms_sent'] == 1) {
            $response = "Exito";
        } else {
            $response = "Error";
        }  
        
        $datos = [
            "id" => $id,
            "proveedor_id" => $proveedor_id,
            "msisdn" => $msisdn,
            "message" => $message,
            "response" => $response,
            "date" => date('Y-m-d H:i:s')				
        ];

        $this->insMainModel->insertSMS($datos);
            
        return $response;
    }

    public function sendSmsUp($to, $mensaje, $api_key, $from) {
        date_default_timezone_set('America/Tegucigalpa');

        $send_at = date("Y-m-d H:i:s");

        $request = '{
            "api_key":"'.$api_key.'",
            "concat":1,
            "messages":[
                {
                    "from":"'.$from.'",
                    "to":"'.$to.'",
                    "text":"'.$mensaje.'",
                    "send_at":"'.$send_at.'"
                }
            ]
        }';

        $url = $this->base_url; // Usar la URL base de la instancia actual

        $headers = array('Content-Type: application/json');
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);

        $result = curl_exec($ch);

        if (curl_errno($ch) != 0 ){
            die("curl error: ".curl_errno($ch));
        }

        return $result;
    }
}

//CONSULTAR LA URL, API KEY

$insMainModel = new mainModel();

$result = $insMainModel->sms_proveedor();
$API_KEY = "";
$URL = "";
$proveedor_id = "";

if ($result->num_rows > 0) {
    // Obtener la primera fila de resultados
    $row = $result->fetch_assoc();

    $API_KEY = $row['api_key'];
    $URL = $row['url'];
    $proveedor_id = $row['proveedor_id'];
    
    $apiClientClaro = new ApiClient($URL);
    $SMSTEXT = "CLINICARE: Mensaje de Prueba";
    $SMSNUMBER = "50497079577";
    //$API_KEY = "IzOWPWxP2fLR2vcCLjXZ5eB1YtC2aYwh";

    $responseClaro = $apiClientClaro->sendSmsClaro($SMSNUMBER, $SMSTEXT, $proveedor_id, $API_KEY);

    echo $responseClaro;
} else {
    echo "No se encontraron resultados en la consulta a la base de datos.";
}