<?php

class Autorizacion
{
    /** @var string ruta test */
    // private static $api = 'http://intranet.idiem.cl:3000/api';
    private static $api = 'http://172.17.92.60:3000/api';
    // private static $api = 'http://146.83.11.239:4000/api';
    // private static $api = 'http://146.83.11.239:3000/api';
    private static $token;

    private static $log;

    public static function newLogin($comesFromCron=false){
        $loginToken = array( 'token' => null, 'message' => null  );
        if($comesFromCron){
            try{
                //valida el servidor
                if ($_SERVER['SERVER_ADDR'] != '200.9.100.62') {
                    $ip = 'http://172.17.92.60:3000';
                } else {
                    $ip = 'http://intranet.idiem.cl:3000';
                }
                //url api
                $url = $ip."/api/core/login-functionary";
                //user statico para api
                $body = [
                    'username' => '99999999',
                    'password' => '09v9085a'
                ];
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($body, '', '&'));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                //valida si hay problemas ejecutando el curl
                if($server_output = curl_exec($ch) === false){
                    return $loginToken = array( 'message' => 'Curl error: ' . curl_error($ch)  );
                }else{
                    $server_output = json_decode(curl_exec($ch), true);
                }
                curl_close($ch);

                if (isset($server_output['data']['token'])) {
                    return $loginToken = array( 'token' => $server_output['data']['token'],
                                                'message' => $server_output['message']);
                } else {
                    return $loginToken = array( 'message' => 'Error en el newLogin');
                }
            }catch(Exception $e){
                return $loginToken = array( 'message' => 'error: '.$e->getMessage());
            }
        }else{
            return $loginToken = array( 'token' => $_SESSION['token-api-dti'],
                                        'message' => 'welcome');
        }
    }
    
    public static function login()
    {

        // $data = [
        //     'username' => '12216139',
        //     'password' => '12216139'
        // ];
        $data = [
            'username' => '99999999',
            'password' => '09v9085a'
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, self::$api . '/login');
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data, '', '&'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $server_output = json_decode(curl_exec($ch), true);
        curl_close($ch);

        self::$token = $server_output['data']['token'];
    }

    private function log($id = null, $response)
    {
        $link = InfoNucleo::getConexionDB('idiem_api_log');

        if (!empty($id)) :
            $response = json_encode($response);
            $sql = "UPDATE dhc_samplingIntranet
                    SET estado = 'ok',
                    response = '{$response}'
                    WHERE id = {$id}";
            $link->query($sql);
            return true;
        else :
            if (self::$log['getTestTubes']['table'] == 'REPORTES_LABORATORIO_VI�A') :
                self::$log['getTestTubes']['table'] = 'REPORTES_LABORATORIO_VINA';
            endif;

            $persona = $_SESSION['kernel']['persona']['PERS_RUT'];
            $objeto = self::$log['getTestTubes']['sampling_number'];
            $grupo = utf8_encode($_SESSION['idiem_seguimiento']['gru_id']);
            $data = array_map('utf8_encode', $data);
            $data = json_encode(self::$log, true);
            $envio = date('Y-m-d H:i:s');
            $sql = "INSERT INTO `dhc_samplingIntranet` SET
                persona = '{$persona}',
                objeto = '{$objeto}',
                grupo = '{$grupo}',
                request = '{$data}',
                estado = 'pendiente',
                envio = '{$envio}'
            ";
            $res = $link->query($sql);
            return mysql_insert_id();
        endif;
    }

    private function getPerson($id)
    {
        self::$log['getPerson']['id'] = $id;
        $link = InfoNucleo::getConexionDB('idiem');
        $sql = "SELECT * FROM PERSONAS WHERE PERS_ID = $id LIMIT 1";
        $res = $link->query($sql);

        $data = $res->fetchRow(DB_FETCHMODE_ASSOC);
        if ($data['PERS_ID']) :
            $person = [
                'oldId' => utf8_encode($data['PERS_ID']),
                'name' => utf8_encode($data['PERS_NOM1']),
                'secondName' => utf8_encode($data['PERS_NOM2']),
                'surname' => utf8_encode($data['PERS_AP_PATERNO']),
                'secondSurname' => utf8_encode($data['PERS_AP_MATERNO']),
                'username' => utf8_encode($data['PERS_RUT'])
            ];
            if ($data['PERS_E_MAIL']) :
                $person['email'] = utf8_encode($data['PERS_E_MAIL']);
            endif;
        endif;

        return $person;
    }

    private function getPlant($id, $clientId, $gru_id)
    {
        self::$log['getPlant']['id'] = $id;
        self::$log['getPlant']['clientId'] = $clientId;
        $link = InfoNucleo::getConexionDB('idiem_seguimiento');
        
        $sql = "SELECT PLA_ID, PLA_NOMBRE, PLA_SINONIMO FROM PLANTAS WHERE PLA_ID = $id AND PLA_CLI_ID = $clientId  AND PLA_GRU_ID = '{$gru_id}' AND PLA_ESTADO = 1 ORDER BY PLA_NOMBRE ASC LIMIT 1";

        $res = $link->query($sql);

        $data = $res->fetchRow(DB_FETCHMODE_ASSOC);

        // FIXME: Cuando query no obtenga datos realizar nueva b�squeda para evitar env�ar datos vacios
        if (!isset($data)) :
            $data = self::getPlantById($id, $gru_id);
        endif;

        $plant = [
            'oldId' => utf8_encode($data['PLA_ID']),
            'name' => utf8_encode($data['PLA_NOMBRE']),
            'code' => utf8_encode($data['PLA_SINONIMO'])
        ];

        return $plant;
    }

    private function getPlantById($id, $gru_id)
    {
        self::$log['getPlant']['id'] = $id;

        $link = InfoNucleo::getConexionDB('idiem_seguimiento');

        $sql = "SELECT PLA_ID, PLA_NOMBRE, PLA_SINONIMO FROM PLANTAS WHERE PLA_ID = $id AND PLA_GRU_ID = '{$gru_id}' AND PLA_ESTADO = 1 ORDER BY PLA_NOMBRE ASC LIMIT 1";

        $res = $link->query($sql);

        $data = $res->fetchRow(DB_FETCHMODE_ASSOC);

        $plant = [
            'PLA_ID' => isset($data) ? $data['PLA_ID'] : $id,
            'PLA_NOMBRE' => $data['PLA_NOMBRE'],
            'PLA_SINONIMO' => $data['PLA_SINONIMO']
        ];

        return $plant;
    }

    private function getTable($rep_mod_id)
    {
        self::$log['getTable']['rep_mod_id'] = $rep_mod_id;

        switch ($rep_mod_id) {
            case 'arica':
                return 'REPORTES_ANTOFAGASTA';
                break;

            case 'abengoa':
                return 'REPORTES_ABENGOA';
                break;

            case 'antofagasta':
                return 'REPORTES_ANTOFAGASTA';
                break;

            case 'central_norte':
                return 'REPORTES_CENTRAL_NORTE';
                break;

            case 'central_sur':
                return 'REPORTES_CENTRAL_SUR';
                break;

            case 'clc':
                return 'REPORTES_CLC';
                break;

            case 'concepcion':
                return 'REPORTES_CONCEPCION';
                break;

            case 'copiapo':
                return 'REPORTES_COPIAPO';
                break;

            case 'ews':
                return 'REPORTES_EWS';
                break;

            case 'laboratorio_coquimbo':
                return 'REPORTES_LABORATORIO_COQUIMBO';
                break;

            case 'laboratorio_obras':
                return 'REPORTES_LABORATORIO_OBRAS';
                break;

            case 'laboratorio_temuco':
                return 'REPORTES_TEMUCO';
                break;

            case 'ogp1':
                return 'REPORTES_OGP1';
                break;

            case 'programacion_muestreo':
                return 'REPORTES_SANTIAGO';
                break;

            case 'proyecto_antofagasta':
                return 'REPORTES_PROYECTO_ANTOFAGASTA';
                break;

            case 'vinna':
                return 'REPORTES_LABORATORIO_VI�A';
                break;

            case 'iquique':
                return 'REPORTES_IQUIQUE';
                break;

            case 'mantos_verdes':
                return 'REPORTES_MANTOS_VERDES';
                break;
        }
    }

    public static function getRepIDandGruID($samplingNumber)
    {
        self::$log['getRepIDandGruID']['samplingNumber'] = $samplingNumber;

        $link = InfoNucleo::getConexionDB('idiem_seguimiento');
        $sql = "SELECT REP_ID, REP_GRU_ID FROM REPORTES WHERE REP_DATOS LIKE '%numero_muestra\' => \'" . trim($samplingNumber) . "\'%' LIMIT 1;";
        $res = $link->query($sql);

        $data = $res->fetchRow(DB_FETCHMODE_ASSOC);
        return $data;
    }

    private static function getTypeGorH($name, $campo)
    {
        self::$log['getTypeGorH']['name'] = $name;

        $link = InfoNucleo::getConexionDB('idiem_seguimiento');
        $sql = "SELECT * FROM DETALLE_HORMIGONES WHERE REPLACE({$campo}, ',', '.') = REPLACE('$name', ',', '.') LIMIT 1;";
        $res = $link->query($sql);

        $data = $res->fetchRow(DB_FETCHMODE_ASSOC);
        $type = [
            'code' => $data['DET_HOR_SAP'],
            'typeG' => $data['DET_HOR_TIPO_G'] == 1 ? true : false,
            'concreteType' => $data['DET_HOR_TIPO'],
            'fc' => $data['DET_HOR_FC'],
            'reliability' => $data['DET_HOR_CONFIABILIDAD'],
            'grade' => $data['DET_HOR_GRADO']
        ];
        // dd($type);
        return $type;
    }


    private function getTestTubes($table, $rep_id_rep, $sampling_number)
    {
        self::$log['getTestTubes']['table'] = $table;
        self::$log['getTestTubes']['rep_id_rep'] = $rep_id_rep;
        self::$log['getTestTubes']['sampling_number'] = $sampling_number;

        $link = InfoNucleo::getConexionDB('idiem_seguimiento');

        $sql = "SELECT * FROM $table WHERE REP_ID_REP = $rep_id_rep AND REP_NUMERO_MUESTRA = $sampling_number";
        $res = $link->query($sql);

        $typeTestTubes = null;
        $testTubes = [];
        $i = 0;
        while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) :
            $typeTestTubes = $row['REP_TIPO_PROBETA'];
            $testTubes[$i] = [];
            //$testTubes[$row['REP_ID']]['age'] = intval($row['REP_EDAD_ENSAYO']);

            if (strtoupper($row['REP_TIPO_PROBETA']) == '-' || strtoupper($row['REP_TIPO_PROBETA']) == '--') :
                $testTubes[$i]['type'] = null;
            else :
                $testTubes[$i]['type'] = utf8_encode(strtoupper($row['REP_TIPO_PROBETA']));
            endif;

            //$testTubes[$row['REP_ID']]['testTubeNumber'] = $row['REP_ID'];
            $testTubes[$i]['result'] = [];
            // $testTubes[$row['REP_NUM_PROBETA']]['result']['density'] = str_replace(',', '.', $row['REP_DENSIDAD']);
            // $testTubes[$row['REP_NUM_PROBETA']]['result']['resistance'] = str_replace(',', '.', $row['REP_RESISTENCIA']);
            $i++;
        endwhile;

        $i = 0;
        $sql = "SELECT ENS_ID, ENS_REP_ID, ENS_PERS_ID, ENS_DIAS, DATE_FORMAT(ENS_FECHA, '%Y-%m-%d') AS ENS_FECHA, ENS_FECHA_REAL, ENS_DATOS FROM ENSAYOS WHERE ENS_REP_ID = $rep_id_rep ORDER BY ENS_ID";
        $res = $link->query($sql);
        while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) :
            // if (empty($data['carga_maxima'])) :
            //     $data['carga_maxima'] = $data['Carga max'];
            // endif;
            $row['ENS_DATOS'] = str_replace('?', '', utf8_decode($row['ENS_DATOS']));

            eval('$data = ' . $row['ENS_DATOS'] . ';');
            $testTubes[$i]['result']['weigth'] = str_replace(',', '.', $data['Peso']) * 1;
            $testTubes[$i]['result']['maxCharge'] = $data['Carga max'] * 1;
            $testTubes[$i]['result']['calibratedMaxCharge'] = str_replace('.', '', $data['carga_maxima']) * 1;
            $testTubes[$i]['date'] = $row['ENS_FECHA'];

            $testTubes[$i]['result']['longs'] = [];
            $testTubes[$i]['result']['widths'] = [];
            $testTubes[$i]['result']['heights'] = [];
            $testTubes[$i]['result']['diameters'] = [];
            $testTubes[$i]['result']['compresionCharges'] = [];

            $testTubes[$i]['testTubeNumber'] = $data['N Molde'];
            $testTubes[$i]['age'] = intval($row['ENS_DIAS']);

            switch (true):
                case strpos($typeTestTubes, 'cubo') !== false:
                    $testTubes[$i]['result']['widths'] = [];
                    if ($data['a1']) array_push($testTubes[$i]['result']['widths'], str_replace(',', '.', $data['a1']) * 1);
                    if ($data['a2']) array_push($testTubes[$i]['result']['widths'], str_replace(',', '.', $data['a2']) * 1);
                    $testTubes[$i]['result']['longs'] = [];
                    if ($data['b1']) array_push($testTubes[$i]['result']['longs'], str_replace(',', '.', $data['b1']) * 1);
                    if ($data['b2']) array_push($testTubes[$i]['result']['longs'], str_replace(',', '.', $data['b2']) * 1);
                    $testTubes[$i]['result']['heights'] = [];
                    if ($data['h1']) array_push($testTubes[$i]['result']['heights'], str_replace(',', '.', $data['h1']) * 1);
                    if ($data['h2']) array_push($testTubes[$i]['result']['heights'], str_replace(',', '.', $data['h2']) * 1);
                    if ($data['h3']) array_push($testTubes[$i]['result']['heights'], str_replace(',', '.', $data['h3']) * 1);
                    if ($data['h4']) array_push($testTubes[$i]['result']['heights'], str_replace(',', '.', $data['h4']) * 1);
                    // print_a('cubo');
                    break;
                case strpos($typeTestTubes, 'cilindro') !== false:
                    $testTubes[$i]['result']['diameters'] = [];
                    if ($data['d1']) array_push($testTubes[$i]['result']['diameters'], str_replace(',', '.', $data['d1']) * 1);
                    if ($data['d2']) array_push($testTubes[$i]['result']['diameters'], str_replace(',', '.', $data['d2']) * 1);
                    $testTubes[$i]['result']['heights'] = [];
                    if ($data['h1']) array_push($testTubes[$i]['result']['heights'], str_replace(',', '.', $data['h1']) * 1);
                    if ($data['h2']) array_push($testTubes[$i]['result']['heights'], str_replace(',', '.', $data['h2']) * 1);
                    // print_a('cilindro');
                    break;
                case strpos($typeTestTubes, 'viga') !== false:
                    $testTubes[$i]['result']['longs'] = [];
                    if ($data['l1']) array_push($testTubes[$i]['result']['longs'], str_replace(',', '.', $data['l1']) * 1);
                    if ($data['l2']) array_push($testTubes[$i]['result']['longs'], str_replace(',', '.', $data['l2']) * 1);
                    if ($data['l3']) array_push($testTubes[$i]['result']['longs'], str_replace(',', '.', $data['l3']) * 1);
                    $testTubes[$i]['result']['widths'] = [];
                    if ($data['b1']) array_push($testTubes[$i]['result']['widths'], str_replace(',', '.', $data['b1']) * 1);
                    if ($data['b2']) array_push($testTubes[$i]['result']['widths'], str_replace(',', '.', $data['b2']) * 1);
                    if ($data['b3']) array_push($testTubes[$i]['result']['widths'], str_replace(',', '.', $data['b3']) * 1);
                    $testTubes[$i]['result']['heights'] = [];
                    if ($data['h1']) array_push($testTubes[$i]['result']['heights'], str_replace(',', '.', $data['h1']) * 1);
                    if ($data['h2']) array_push($testTubes[$i]['result']['heights'], str_replace(',', '.', $data['h2']) * 1);
                    if ($data['h3']) array_push($testTubes[$i]['result']['heights'], str_replace(',', '.', $data['h3']) * 1);
                    $testTubes[$i]['result']['maxChargeToni'] = str_replace(',', '.', $data['Carga max Toni']) * 1;
                    $testTubes[$i]['result']['modCube'] = str_replace(',', '.', $data['Cubo mod Toni']) * 1;
                    $testTubes[$i]['result']['maxCharge'] = $testTubes[$i]['result']['maxChargeToni'] ? $testTubes[$i]['result']['maxChargeToni'] * 100 : null;
                    // print_a('viga');
                    break;
                case strpos($typeTestTubes, 'hendimiento') !== false:
                    $testTubes[$i]['result']['heights'] = [];
                    if ($data['l1']) array_push($testTubes[$i]['result']['heights'], str_replace(',', '.', $data['l1']) * 1);
                    if ($data['l2']) array_push($testTubes[$i]['result']['heights'], str_replace(',', '.', $data['l2']) * 1);
                    $testTubes[$i]['result']['diameters'] = [];
                    if ($data['d1']) array_push($testTubes[$i]['result']['diameters'], str_replace(',', '.', $data['d1']) * 1);
                    if ($data['d2']) array_push($testTubes[$i]['result']['diameters'], str_replace(',', '.', $data['d2']) * 1);
                    if ($data['d3']) array_push($testTubes[$i]['result']['diameters'], str_replace(',', '.', $data['d3']) * 1);
                    // print_a('hendimiento');
                    break;
                case strpos($typeTestTubes, 'rilem') !== false:
                    $testTubes[$i]['result']['longs'] = [];
                    if ($data['Largo A']) array_push($testTubes[$i]['result']['longs'], str_replace(',', '.', $data['Largo A']) * 1);
                    if ($data['Largo B']) array_push($testTubes[$i]['result']['longs'], str_replace(',', '.', $data['Largo B']) * 1);

                    $testTubes[$i]['result']['heights'] = [];
                    if ($data['Alto']) array_push($testTubes[$i]['result']['heights'], str_replace(',', '.', $data['Alto']) * 1);

                    $testTubes[$i]['result']['widths'] = [];
                    if ($data['Ancho']) array_push($testTubes[$i]['result']['widths'], str_replace(',', '.', $data['Ancho']) * 1);

                    $testTubes[$i]['result']['flextraction'] = str_replace(',', '.', $data['Carga Flexotraccin']) * 1;

                    $testTubes[$i]['result']['compresionCharges'] = [];
                    if ($data['Carga comp 1']) array_push($testTubes[$i]['result']['compresionCharges'], str_replace(',', '.', $data['Carga comp 1']) * 1);
                    if ($data['Carga comp 2']) array_push($testTubes[$i]['result']['compresionCharges'], str_replace(',', '.', $data['Carga comp 2']) * 1);
                    // print_a('rilem');
                    break;
            endswitch;

            $testTubes[$i]['result']['humidity'] = (utf8_decode($data['Humedad']) == "H?meda") ? true : false;
            $testTubes[$i]['result']['hollow'] = ($data['Oquedades'] == 'No') ? false : true;
            $testTubes[$i]['result']['blunt'] = ($data['Despuntes'] == 'No') ? false : true;
            $testTubes[$i]['result']['correct'] = ($data['Tipo falla'] == 'C') ? true : false;
            $i++;
        endwhile;
        return array_values($testTubes);
    }

    private static function entry($gru_id)
    {
        self::$log['entry']['gru_id'] = $gru_id;

        switch ($gru_id):
            case 'idiem.hormigones.hormigones_norte.iquique': // 1834
            case 'idiem.hormigones.hormigones_norte.antofagasta': // 1824
            case 'idiem.hormigones_proyecto.laboratorio.obras': // 2325
            case 'idiem.hormigones.hormigones_centro.copiapo': // 2377
            case 'idiem.hormigones.hormigones_centro.regional_coquimbo': // 2326
            case 'idiem.hormigones.hormigones_centro.vina_hormigones': // 1826
            case 'idiem.hormigones.muestreo.laboratorio': // 1813
            case 'idiem.hormigones.muestreo.central_sur': // ????
            case 'idiem.hormigones.muestreo.central_norte': // ????
            case 'idiem.hormigones.hormigones_sur.sur': // 1830
            case 'idiem.hormigones.hormigones_sur.temuco': // 2321
            case 'idiem.hormigones_proyecto.melon_mantos_verdes': // 3577
                return true;
            default:
                return false;
        endswitch;
    }

    private static function entryModule($module)
    {
        self::$log['entryModule']['module'] = $module;
        return $module == 'sgh_hormigones';
    }

    public static function sendSampling($gru_id, $rep_id, $keepAuth)
    {
        self::$log['sendSampling']['gru_id'] = $gru_id;
        self::$log['sendSampling']['rep_id'] = $rep_id;
        self::$log['sendSampling']['keepAuth'] = $keepAuth;

        if (!self::entry($gru_id)) return;
        try {
            if ($keepAuth) :
                self::$log['Reportes::get2']['rep_id'] = $rep_id;
                self::$log['Reportes::get2']['gru_id'] = $gru_id;
                $data = Reportes::get2($rep_id, $gru_id);
            else :
                self::$log['Reportes::get']['rep_id'] = $rep_id;
                $data = Reportes::get($rep_id);
            endif;
            if (split('-', $data['MUE_MUE_FECHA'])[0] < 2016) return;
            $work = array(
                'oldId' => $data['OBR_ID'],
                'name' => utf8_encode($data['OBR_NOMBRE']),
                'code' => $data['O_CLI_CODIGO'],
                'address' => [
                    'location' => utf8_encode($data['OBR_DIRECCION']),
                    'city' => utf8_encode($data['OBR_COM_COMUNA']),
                    'coords' => [
                        'lat' => ($data['OBR_LATITUD'] ? $data['OBR_LATITUD'] : 0),
                        'lng' => ($data['OBR_LONGITUD'] ? $data['OBR_LONGITUD'] : 0)
                    ]
                ]
            );

            $client = [
                'oldId' => $data['CLI_ID'],
                'rut' => utf8_encode($data['CLI_RUT'] . '-' . $data['CLI_DV']),
                'name' => utf8_encode($data['CLI_NOMBRE'])
            ];

            if (
                strpos(strtolower($client['name']), 'melon') !== false ||
                strpos(strtolower($client['name']), 'mel�n') !== false ||
                strpos(strtolower($client['name']), 'melón') !== false ||
                strpos(strtolower($client['name']), 'lafarge') !== false
            ) :
                $data['CLI_ID'] = 3;
            endif;

            if (!empty($data['MUE_MUE_PERS_ID'])) :
                if (intval($data['MUE_MUE_PERS_ID']) < 0) :
                    $autocontrol = $data['MUE_MUE_PERS_ID'];
                else :
                    $autocontrol = 0;
                endif;
            endif;

            $designer = [
                'other' => utf8_encode($data['datos']['confeccionado_por'])
            ];

            if ($data['datos']['curado_probeta'] == 'Bajo agua') :
                $waterTemperature = $data['datos']['temperatura_agua'];
            else :
                $waterTemperature = $data['MUE_TEMP_CIBA'];
            endif;

            $body =
                [
                    'oldId' => $data['MUE_ID'],
                    'work' => $work,
                    'client' => $client,
                    // 'plant' => self::getPlant($data['datos']['nro_planta'], $data['CLI_ID'], $gru_id),
                    // 'designer' => $designer,
                    // Cambio en forma de enviar la planta y el confeccionador, hacia el nuevo modelo viajar el id de mongo dejar como objeto plant: {plantId: 123456}, designer: {designerId: 123456}
                    'plant' => ['plantId' => $data['datos']['id_planta']],
                    'designer' => ['designerId' => $data['datos']['designerId'], 'other' => utf8_encode($data['datos']['confeccionado_por'])],
                    'user' => self::getPerson($data['MUE_MUE_PERS_ID']),
                    'autocontrol' => $autocontrol,
                    'samplingNumber' => $data['MUE_MUE_NUMERO'],
                    'reportNumber' => is_numeric($data['REP_NUM_INFORMAT']) ? $data['REP_NUM_INFORMAT'] : null,
                    'builder' => utf8_encode($data['O_CLI_CONTRATISTA']),
                    'samplingNumberDay' => is_numeric(utf8_encode($data['MUE_MUE_NUMERO_DIARIO'])) ? $data['MUE_MUE_NUMERO_DIARIO'] : null,
                    'date' => $data['MUE_MUE_FECHA'],
                    'time' => $data['MUE_MUE_HORA'],
                    'arrival' => [
                        'time' => $data['datos']['hora_llegada'],
                        'coords' => [
                            'lat' => 0,
                            'lng' => 0
                        ]
                    ],
                    'departure' => [
                        'time' => $data['datos']['hora_salida'],
                        'coords' => [
                            'lat' => 0,
                            'lng' => 0
                        ]
                    ],
                    'samplingTime' => utf8_encode($data['MUE_MUE_HORA']),
                    'departureTimePlantTruck' => utf8_encode($data['datos']['hora_salida_planta']),
                    'arrivalTimeWorkTruck' => utf8_encode($data['datos']['hora_llegada']),
                    'dischargeTimeStart' => utf8_encode($data['datos']['hora_descarga']),
                    'truckNumber' => utf8_encode($data['datos']['nro_camion']),
                    'documentNumber' => utf8_encode($data['datos']['nro_guia']),
                    'quantity' => is_numeric(utf8_encode($data['datos']['cantidad_hormigon'])) ? utf8_encode($data['datos']['cantidad_hormigon']) : null,
                    // 'productCode' => utf8_encode($data['datos']['hor_codigo']),
                    'productType' => utf8_encode($data['datos']['hor_tipo']),
                    'putIn' => utf8_encode($data['datos']['colocado_en']),
                    'additive' => [
                        'description' => utf8_encode($data['datos']['aditivos']),
                        'quantity' => is_numeric(utf8_encode($data['datos']['cantidad_aditivo'])) ? utf8_encode($data['datos']['cantidad_aditivo']) : null,
                        'unity' => utf8_encode($data['datos']['unidad_aditivo'])
                    ],
                    'compactionWorkType' => [
                        'description' => utf8_encode($data['datos']['tipo_compactacion_obra'])
                    ],
                    'compactionSamplingType' => [
                        'description' => utf8_encode($data['datos']['tipo_compactacion'])
                    ],
                    'curedType' => [
                        'description' => utf8_encode($data['datos']['curado_probeta'])
                    ],
                    'temperature' => is_numeric(utf8_encode($data['datos']['temp_ambiente'])) ? utf8_encode($data['datos']['temp_ambiente']) : null,
                    'mixTemperature' => is_numeric(utf8_encode($data['datos']['temp_hormigon'])) ? utf8_encode($data['datos']['temp_hormigon']) : null,
                    'settlement' => utf8_encode($data['datos']['asentamiento']),
                    'cibaCuredTemperature' => is_numeric(utf8_encode($waterTemperature)) ? utf8_encode($waterTemperature) : null,
                    'extractedTo' => is_numeric(utf8_encode($data['datos']['extraccion_a'])) ? utf8_encode($data['datos']['extraccion_a']) : null,
                    'cibaNumber' => is_numeric(utf8_encode($data['MUE_NUMERO_CIBA'])) ? utf8_encode($data['MUE_NUMERO_CIBA']) : null,
                    'curedTypeLab' => utf8_encode($data['datos']['curado_probeta_lab']),
                    'description' => utf8_encode($data['datos']['observaciones']),
                    'priority' => ($data['MUE_MUE_PRIORIDAD'] == 'SI' ? true : false),
                    'from' => 'INTRANET',
                    'status' => self::status($data['MUE_MUE_ESTADO']),
                    'update' => true,
                    'authorized' => true,
                    'costCenter' => $gru_id,
                    'orderNumber' => is_numeric(utf8_encode($data['MUE_NUM_PEDIDO'])) ? utf8_encode($data['MUE_NUM_PEDIDO']) : null,
                    'samplingType' => $data['MUE_MUE_TIPO'],
                    'testTubeType' => $data['MUE_HOR_PROBETA_TIPO'],
                    'machine' => $data['datos']['maquina'],
                    'requestSampling' => $data['MUE_MUE_SOLICITADA']
                    // 'productNotRt' => self::getTypeGorH($data['datos']['mue_hor_tipo'])
                ];

            if (isset($data['datos']['hor_codigo'])) :
                $typeGorH = self::getTypeGorH(str_replace(',', '.', $data['datos']['hor_codigo']), 'DET_HOR_SAP');
            else :
                $typeGorH = self::getTypeGorH(str_replace(',', '.', $data['datos']['hor_tipo']), 'DET_HOR_HORMIGON');
            endif;

            // if (!empty($data['datos']['hor_tipo'])) :
            //     $typeGorH = self::getTypeGorH(str_replace(',', '.', $data['datos']['hor_tipo']), 'DET_HOR_HORMIGON');
            // else :
            //     $typeGorH = self::getTypeGorH(str_replace(',', '.', $data['datos']['hor_codigo']), 'DET_HOR_SAP');
            // endif;

            if ($typeGorH) :
                $body['productNotRt'] = $typeGorH['typeG'];
                $body['productCode'] = $typeGorH['code'] ? utf8_encode($data['datos']['hor_codigo']) : utf8_encode($data['datos']['hor_tipo']);
            endif;

            if ($typeGorH && isset($typeGorH['code'])) :
                $body['concreteDetails'] = array(
                    'concreteType' => utf8_encode($typeGorH['concreteType']),
                    'fc' => utf8_encode($typeGorH['fc']),
                    'reliability' => utf8_encode($typeGorH['reliability']),
                    'grade' => utf8_encode($typeGorH['grade'])
                );
            endif;

            if (self::typeTestTubes($data['MUE_MUE_TIPO'], $data['MUE_HOR_PROBETA_TIPO'])) :
                $body['testTubes'] = self::getTestTubes(self::getTable($data['REP_MOD_ID']), $data['REP_ID'], $data['MUE_MUE_NUMERO']);
            else :
                $body['testTubes'] = [];
            endif;

            if ($keepAuth) :
                $body['keepAuth'] = true;
            endif;
            $body['reception'] = [];
            if ($data['MUE_RET_FECHA'] && $data['MUE_RET_FECHA'] != '0000-00-00') $body['reception']['date'] = (string) $data['MUE_RET_FECHA'] . ' ' . (string) $data['MUE_RET_HORA'];
            if ($data['MUE_RET_OBSERVACIONES']) $body['reception']['observation'] = utf8_encode($data['MUE_RET_OBSERVACIONES']);
            if ($data['MUE_NUMERO_CIBA_RETIRO']) $body['reception']['cibaNumber'] = utf8_encode($data['MUE_NUMERO_CIBA_RETIRO']);

            if (count($body['reception']) == 0) :
                unset($body['reception']);
            endif;

            if (!empty($data['datos']['dens_aparente'])) :
                $body['apparentDensity'] = $data['datos']['dens_aparente'];
            endif;

            self::console_log(json_encode($body));
            self::console_log($body);

            try {
                self::console_log(self::custom_json_encode($body));
            } catch (Exception $e) {
                self::console_log($e);
            }

            $result = self::sendData('/dhc/sampling-intranet', json_encode($body), 'POST');

            if ($result->done) :
                if ($keepAuth) :
                    if (strpos($_SERVER['REQUEST_URI'], 'send.php') !== false) {
                        if ($_POST['show'] == 'on') {
                            print_a(json_encode($body));
                        }
                        if ($_POST['showPHP'] == 'on') {
                            print_a($body);
                        }
                        echo '<br><b>' . $body['samplingNumber'] . '</b> MUESTRA ENVIADA';
                    } else {
                        KERNEL::mExito($body['samplingNumber'] . '</b> MUESTRA ENVIADA');
                    } else :
                    KERNEL::mExito('Se registro exitosamente en la nueva base de datos');
                endif;
            else :
                if (strpos($_SERVER['REQUEST_URI'], 'send.php') !== false) :
                    if ($_POST['show'] == 'on') {
                        print_a(json_encode($body));
                    }
                    if ($_POST['showPHP'] == 'on') {
                        print_a($body);
                    }
                    echo '<b>ERROR</b> ' . $result->message;
                endif;
            endif;
        } catch (Exception $e) { }
    }

    public static function sendSamplingFixPlantas($gru_id, $rep_id, $post)
    {
        self::$log['sendSamplingFixPlantas']['rep_id'] = $rep_id;
        self::$log['sendSamplingFixPlantas']['gru_id'] = $gru_id;
        self::$log['sendSamplingFixPlantas']['post'] = $post;

        if (!self::entry($gru_id)) return 'Error en el grupo';
        try {
            $data = Reportes::get2($rep_id, $gru_id);
            if (split('-', $data['MUE_MUE_FECHA'])[0] < 2016) return 'Error cruce con muestreo';
            $work = array(
                'oldId' => $data['OBR_ID'],
                'name' => utf8_encode($data['OBR_NOMBRE']),
                'code' => $data['O_CLI_CODIGO'],
                'address' => [
                    'location' => utf8_encode($data['OBR_DIRECCION']),
                    'city' => utf8_encode($data['OBR_COM_COMUNA']),
                    'coords' => [
                        'lat' => ($data['OBR_LATITUD'] ? $data['OBR_LATITUD'] : 0),
                        'lng' => ($data['OBR_LONGITUD'] ? $data['OBR_LONGITUD'] : 0)
                    ]
                ]
            );

            $client = [
                'oldId' => $data['CLI_ID'],
                'rut' => utf8_encode($data['CLI_RUT'] . '-' . $data['CLI_DV']),
                'name' => utf8_encode($data['CLI_NOMBRE'])
            ];

            $designer = [
                'other' => utf8_encode($data['datos']['confeccionado_por'])
            ];

            if ($data['datos']['curado_probeta'] == 'Bajo agua') :
                $waterTemperature = $data['datos']['temperatura_agua'];
            else :
                $waterTemperature = $data['MUE_TEMP_CIBA'];
            endif;

            $body =
                [
                    'oldId' => $data['MUE_ID'],
                    'work' => $work,
                    'client' => $client,
                    'plant' => self::getPlant($data['datos']['nro_planta'], 3),
                    'designer' => $designer,
                    'user' => self::getPerson($data['MUE_MUE_PERS_ID']),
                    'samplingNumber' => $data['MUE_MUE_NUMERO'],
                    'reportNumber' => is_numeric($data['REP_NUM_INFORMAT']) ? $data['REP_NUM_INFORMAT'] : null,
                    'builder' => utf8_encode($data['O_CLI_CONTRATISTA']),
                    'samplingNumberDay' => is_numeric(utf8_encode($data['MUE_MUE_NUMERO_DIARIO'])) ? $data['MUE_MUE_NUMERO_DIARIO'] : null,
                    'date' => $data['MUE_MUE_FECHA'],
                    'time' => $data['MUE_MUE_HORA'],
                    'arrival' => [
                        'time' => $data['datos']['hora_llegada'],
                        'coords' => [
                            'lat' => 0,
                            'lng' => 0
                        ]
                    ],
                    'departure' => [
                        'time' => $data['datos']['hora_salida'],
                        'coords' => [
                            'lat' => 0,
                            'lng' => 0
                        ]
                    ],
                    'samplingTime' => utf8_encode($data['MUE_MUE_HORA']),
                    'departureTimePlantTruck' => utf8_encode($data['datos']['hora_salida_planta']),
                    'arrivalTimeWorkTruck' => utf8_encode($data['datos']['hora_llegada']),
                    'dischargeTimeStart' => utf8_encode($data['datos']['hora_descarga']),
                    'truckNumber' => utf8_encode($data['datos']['nro_camion']),
                    'documentNumber' => utf8_encode($data['datos']['nro_guia']),
                    'quantity' => is_numeric(utf8_encode($data['datos']['cantidad_hormigon'])) ? utf8_encode($data['datos']['cantidad_hormigon']) : null,
                    // 'productCode' => utf8_encode($data['datos']['hor_codigo']),
                    'productType' => utf8_encode($data['datos']['hor_tipo']),
                    'putIn' => utf8_encode($data['datos']['colocado_en']),
                    'additive' => [
                        'description' => utf8_encode($data['datos']['aditivos']),
                        'quantity' => is_numeric(utf8_encode($data['datos']['cantidad_aditivo'])) ? utf8_encode($data['datos']['cantidad_aditivo']) : null,
                        'unity' => utf8_encode($data['datos']['unidad_aditivo'])
                    ],
                    'compactionWorkType' => [
                        'description' => utf8_encode($data['datos']['tipo_compactacion_obra'])
                    ],
                    'compactionSamplingType' => [
                        'description' => utf8_encode($data['datos']['tipo_compactacion'])
                    ],
                    'curedType' => [
                        'description' => utf8_encode($data['datos']['curado_probeta'])
                    ],
                    'temperature' => is_numeric(utf8_encode($data['datos']['temp_ambiente'])) ? utf8_encode($data['datos']['temp_ambiente']) : null,
                    'mixTemperature' => is_numeric(utf8_encode($data['datos']['temp_hormigon'])) ? utf8_encode($data['datos']['temp_hormigon']) : null,
                    'settlement' => utf8_encode($data['datos']['asentamiento']),
                    'cibaCuredTemperature' => is_numeric(utf8_encode($waterTemperature)) ? utf8_encode($waterTemperature) : null,
                    'extractedTo' => is_numeric(utf8_encode($data['datos']['extraccion_a'])) ? utf8_encode($data['datos']['extraccion_a']) : null,
                    'cibaNumber' => is_numeric(utf8_encode($data['MUE_NUMERO_CIBA'])) ? utf8_encode($data['MUE_NUMERO_CIBA']) : null,
                    'curedTypeLab' => utf8_encode($data['datos']['curado_probeta_lab']),
                    'description' => utf8_encode($data['datos']['observaciones']),
                    'priority' => ($data['MUE_MUE_PRIORIDAD'] == 'SI' ? true : false),
                    'from' => 'INTRANET',
                    'status' => self::status($data['MUE_MUE_ESTADO']),
                    'update' => true,
                    'authorized' => true,
                    // 'costCenter' => utf8_encode($_SESSION['idiem_seguimiento']['gru_id']),
                    'costCenter' => $gru_id,
                    'orderNumber' => is_numeric(utf8_encode($data['MUE_NUM_PEDIDO'])) ? utf8_encode($data['MUE_NUM_PEDIDO']) : null,
                    'samplingType' => $data['MUE_MUE_TIPO'],
                    'testTubeType' => $data['MUE_HOR_PROBETA_TIPO']
                    // 'productNotRt' => self::getTypeGorH($data['datos']['mue_hor_tipo'])
                ];

            if ($data['MUE_MUE_MOTIVO']) :
                $body['lostTripReason'] = $data['MUE_MUE_MOTIVO'];
            endif;

            if (!empty($data['datos']['hor_tipo'])) :
                $typeGorH = self::getTypeGorH(str_replace(',', '.', $data['datos']['hor_tipo']), 'DET_HOR_HORMIGON');
            else :
                $typeGorH = self::getTypeGorH(str_replace(',', '.', $data['datos']['hor_codigo']), 'DET_HOR_SAP');
            endif;

            if ($typeGorH) :
                $body['productNotRt'] = $typeGorH['typeG'];
                $body['productCode'] = $typeGorH['code'] ? $typeGorH['code'] : utf8_encode($data['datos']['hor_tipo']);
            endif;

            if (self::typeTestTubes($data['MUE_MUE_TIPO'], $data['MUE_HOR_PROBETA_TIPO'])) :
                $body['testTubes'] = self::getTestTubes(self::getTable($data['REP_MOD_ID']), $data['REP_ID'], $data['MUE_MUE_NUMERO']);
            else :
                $body['testTubes'] = [];
            endif;
            // self::console_log(json_encode($body));
            $result = self::sendData('/dhc/sampling-intranet', json_encode($body), 'POST');

            if ($result->done) :
                KERNEL::mExito('Se registro exitosamente en la nueva base de datos');
            endif;
            return $result->done;
        } catch (Exception $e) {
            return $e;
        }
    }

    private static function typeTestTubes($type, $testTubeType)
    {
        self::$log['typeTestTubes']['type'] = $type;
        self::$log['typeTestTubes']['testTubeType'] = $testTubeType;

        $tipos = array(
            '',
            '-',
            'densidad',
            'aire',
            'ambos',
            'cono',
            'temp',
            'conotemp',
            'elasticidad',
            'retraccion',
            'porcentaje_cromo',
            'permeabilidad',
            'impermeabilidad'
        );

        if ((in_array($type, $tipos) || !$type) && (strpos($testTubeType, 'cubo') !== false ||
                strpos($testTubeType, 'cilindro') !== false ||
                strpos($testTubeType, 'viga') !== false ||
                strpos($testTubeType, 'hendimiento') !== false ||
                strpos($testTubeType, 'rilem') !== false)
        ) :
            return true;
        endif;
        return false;
    }

    private static function getSignatories($sampling_number, $gru_id)
    {
        self::$log['getSignatories']['sampling_number'] = $sampling_number;
        self::$log['getSignatories']['gru_id'] = $gru_id;

        $link = InfoNucleo::getConexionDB('idiem_seguimiento');
        $sql = "SELECT REP_DATOS FROM REPORTES WHERE REP_DATOS LIKE '%$sampling_number%' AND REP_GRU_ID = '$gru_id' LIMIT 1";

        $res = $link->query($sql);

        $data = $res->fetchRow(DB_FETCHMODE_ASSOC);
        eval('$rep_datos = ' . $data['REP_DATOS'] . ';');
        if ($rep_datos['firmante1'] && $rep_datos['firmante2']) :
            return [
                'signatory1' => $rep_datos['firmante1'],
                'signatory2' => $rep_datos['firmante2']
            ];
        endif;

        return null;
    }

    public static function getSignature($doc_id)
    {
        $link = InfoNucleo::getConexionDB('firma');
        $sql = "SELECT FIR_FECHA FROM FIRMAS WHERE FIR_DOC_ID = $doc_id LIMIT 1;";
        return $link->query($sql)->fetchRow(DB_FETCHMODE_ASSOC)['FIR_FECHA'];
    }

    public static function getDocument($gru_id, $sampling_number)
    {
        $link = InfoNucleo::getConexionDB('firma');
        $sql = "SELECT DOC_ID, DOC_MODULO, DOC_KEY, DATE_FORMAT(DOC_FECHA_VALIDACION, '%Y-%m-%d %H:%i:%S') AS DOC_FECHA_VALIDACION, DOC_METADATOS, DOC_ID_MODULO FROM DOCUMENTOS WHERE DOC_GRU_ID LIKE '$gru_id' AND DOC_METADATOS LIKE '%$sampling_number%' AND DOC_ESTADO = 1 AND DOC_PUBLICADO = 1;";
        $res = $link->query($sql);
        while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) :
            eval('$metadatos = ' . $row['DOC_METADATOS'] . ';');
            if ($metadatos['numero_muestra'] == $sampling_number) :
                self::publish(
                    $gru_id,
                    $row['DOC_MODULO'],
                    $row['DOC_KEY'],
                    $row['DOC_FECHA_VALIDACION'],
                    self::getSignature($row['DOC_ID']),
                    $metadatos,
                    $row['DOC_ID_MODULO']
                );
            endif;
        endwhile;
    }

    public static function publish($gru_id, $module, $doc_key, $date_validated, $date_signatured, $metadatos, $numberDocument)
    {
        self::$log['publish']['gru_id'] = $gru_id;
        self::$log['publish']['module'] = $module;
        self::$log['publish']['doc_key'] = $doc_key;
        self::$log['publish']['date_validated'] = $date_validated;
        self::$log['publish']['date_signatured'] = $date_signatured;
        self::$log['publish']['metadatos'] = var_export($metadatos, true);

        if (!self::entryModule($module) && !self::entry($gru_id)) return;

        $signatories = self::getSignatories($metadatos['numero_muestra'], $gru_id);

        try {
            $body =
                [
                    'hash' => $doc_key,
                    'validation' => [
                        'validated' => true,
                        'date' => $date_validated
                    ],
                    'sign' => [
                        'signatured' => true,
                        'date' => $date_signatured,
                        'signatory1' => utf8_encode($signatories['signatory1']),
                        'signatory2' => utf8_encode($signatories['signatory2'])
                    ],
                    'number' => $numberDocument
                ];

            if ($metadatos['estado']) :
                $body['status'] = strtoupper($metadatos['estado']);
            else :
                $body['status'] = null;
            endif;
            //print_a($body);
            //$body = array_map('utf8_encode', $body);
            //print_a($body);
            //print_a(json_encode($body, true));
            $result = self::sendData('/dhc/sampling-set-report/' . $metadatos['numero_muestra'] . '/' . $gru_id, json_encode($body, true), 'PUT');
            if ($result->done) :
                KERNEL::mExito('Documento publicado en la nueva base de datos');
            endif;
        } catch (Exception $e) {
            // print_a($e);die;
        }
    }

    public static function unpublish($gru_id, $doc_key)
    {
        self::$log['unpublish']['gru_id'] = $gru_id;
        self::$log['unpublish']['doc_key'] = $doc_key;

        if (!self::entry($gru_id)) return;
        try {
            $body =
                [
                    'hash' => $doc_key
                ];

            $result = self::sendData('/dhc/sampling-unset-report/', json_encode($body), 'PUT');
            if ($result->done) :
                KERNEL::mExito('Documento despublicado en la nueva base de datos');
            endif;
        } catch (Exception $e) {
            // print_a($e);
        }
    }

    public static function setStatus($sampling_number, $status)
    {
        self::$log['setStatus']['sampling_number'] = $sampling_number;
        self::$log['setStatus']['status'] = $status;

        //if (!self::entryModule($module) && !self::entry()) return;

        try {
            $body = [
                'isNulled' => $status
            ];
            $result = self::sendData('/dhc/sampling-cancel/' . $sampling_number, json_encode($body), 'PUT');

            if ($result->done) :
                KERNEL::mExito('Muestra modificada en la nueva base de datos');
            endif;
        } catch (Exception $e) {
            // print_a($e);die;
        }
    }

    private static function sendData($url, $data, $method)
    {
        self::$log['sendData']['url'] = $url;
        self::$log['sendData']['method'] = $method;
        self::$log['sendData']['data'] = json_decode($data, true);

        self::login();

        $headers = [
            'Authorization: ' . self::$token,
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data)
        ];

        $idLog = self::log();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::$api . $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $server_output = json_decode(curl_exec($ch));
        curl_close($ch);

        self::log($idLog, $server_output);

        return $server_output;
    }

    private function custom_json_encode($data)
    {
        if (json_encode($data) === false) {
            throw new Exception(json_last_error());
        }
    }

    private function console_log($data)
    {
        echo '<script>';
        echo 'console.log(' . $data . ')';
        echo '</script>';
    }

    public static function updateFields($samplingNumber, $groupId, $fields)
    {
        self::$log['updateFields']['samplingNumber'] = $samplingNumber;
        self::$log['updateFields']['groupId'] = $groupId;
        self::$log['updateFields']['fields'] = $fields;

        try {
            $result = self::sendData('/dhc/sampling-update/' . $samplingNumber . '/' . $groupId, json_encode($fields), 'PUT');
            if ($result->done) :
                KERNEL::mExito('Se registro exitosamente en la nueva base de datos');
            endif;
        } catch (Exception $e) {
            KERNEL::mError('Hubo un problema en actualizar el registro en la nueva base de datos');
        }
    }

    private static function status($state)
    {
        switch ($state) {
            case 'Tomada':
                return 'ATENDIDA';

            case 'Viaje Perdido':
                return 'VIAJE PERDIDO';

            case 'Nula':
                return 'NULA';

            case 'Perdida por Laboratorio':
                return 'PERDIDA POR LABORATORIO';

            case 'Pendiente':
                return 'PENDIENTE';

            case 'Eliminada':
                return 'ELIMINADA';

            case 'Reprogramada':
                return 'REPROGRAMADA';
        }
    }

    public static function changeStatus($groupId, $samplingNumber, $state, $reason)
    {
        self::$log['changeStatus']['groupId'] = $groupId;
        self::$log['changeStatus']['samplingNumber'] = $samplingNumber;
        self::$log['changeStatus']['state'] = $state;
        self::$log['changeStatus']['reason'] = $reason;

        $body = null;

        switch ($state):
            case 'Tomada':
                $body['status'] = self::status($state);
                break;

            case 'Viaje Perdido':
                $body['status'] = self::status($state);
                $body['lostTripReason'] = $reason;
                break;

            case 'Nula':
                $body['isNulled'] = true;
                $body['status'] = self::status($state);
                break;

            case 'Perdida por Laboratorio':
                $body['status'] = self::status($state);
                $body['lostTripReason'] = $reason;
                break;

            case 'Pendiente':
                $body['status'] = self::status($state);
                break;

            case 'Eliminada':
                $body['status'] = self::status($state);
                break;

            case 'Reprogramada':
                $body['status'] = self::status($state);
                $body['lostTripReason'] = $reason;
                break;

        // case 'Nula':
        //     $body['isNulled'] = true;
        //     break;
        // case 'Viaje Perdido':
        //     $body['status'] = 'VIAJE PERDIDO';
        //     $body['lostTripReason'] = $reason;
        //     break;
        // case 'Reprogramada':
        //     $body['status'] = 'REPROGRAMADA';
        //     $body['lostTripReason'] = $reason;
        //     break;
        // case 'Perdida por Laboratorio':
        //     $body['status'] = 'PERDIDA POR LABORATORIO';
        //     $body['lostTripReason'] = $reason;
        //     break;
        // case 'Pendiente':
        //     $body['status'] = 'PENDIENTE';
        //     break;
        // case 'Tomada':
        //     $body['status'] = 'TOMADA';
        //     break;
        endswitch;

        if ($body) {
            $result = self::sendData('/dhc/sampling-update/' . $samplingNumber . '/' . $groupId, json_encode($body), 'PUT');
            if ($result->done) :
                KERNEL::mExito('Se modific� el estado en la nueva base de datos');
            endif;
        }
    }
}
