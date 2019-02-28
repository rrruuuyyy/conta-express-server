<?php
    use Firebase\JWT\JWT;

    class Auth {

        private static $secret_key = '11a45ra1f5%/&($%"64531$%57y6';
        private static $encrypt = ['HS256'];

        public static function SignIn($data){
            $time = time();
            
            $token = array(
                'inicio' => $time, // Tiempo que inició el token
                'expiracion' => $time + (60*240),
                'data' => [ // información del usuario
                    'id' => $data->idusuario,
                    'nombre' => $data->nombre,
                    'apellidos' => $data->apellidos,
                    ]
                );
            $mensaje = array(
                'token' => JWT::encode($token, self::$secret_key),
                'expiracion' => $token['expiracion'],
            );
            return $mensaje;
        }

        public static function Check($token){

            if(empty($token))
            {
                $mensaje = array(
                    'status' => false,
                    'mensaje' => 'El token enviado no es valido',
                    'error' => 'token' 
                );
                return $mensaje;
            }

            $time = time();
            
            $decode = JWT::decode(
                $token,
                self::$secret_key,
                self::$encrypt
            );

            if($decode->expiracion < $time){
                $mensaje = array(
                    'status' => false,
                    'mensaje' => 'El token enviado expiro',
                    'error' => 'token' 
                );
                return $mensaje;
            }
            
        }
    }