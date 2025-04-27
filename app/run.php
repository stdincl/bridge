<?php
    require_once '../../vendor/autoload.php';

    use Exception;
    
    use stdincl\bridge\Reflector;
    use stdincl\bridge\exception\BridgeException;

    # Bridge acepta todas las peticiones OPTIONS
    if($_SERVER['REQUEST_METHOD']=='OPTIONS'){
        http_response_code(200);
        return;
    }
    session_start();
    header('Content-Type:application/json');
    try {
        try {
            # Intenta la ejecucion del metodo solicitado
            $result = Reflector::execute(); 
            http_response_code(200);
        } catch (Exception $e) {
            # Ante errores no generados por Bridge se genera uno con un mensaje simple
            throw new BridgeException($e->getMessage());
        }
    } catch (BridgeException $e) {
        # Si ocurre un error generar respuesta
        $result = $e->response();
        http_response_code(400);
    } 
    # Generar json con datos resultantes
    $json = json_encode($result,JSON_NUMERIC_CHECK);
    # Retornar tamaño de la respuesta
    header('Content-Length: '.strlen($json));
    # Output
    echo $json;
?>