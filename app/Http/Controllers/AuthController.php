<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;


class InvalidUsersException extends \Exception {}
class InvalidCredentialsException extends \Exception {}
class UnauthorizedUserException extends \Exception {}

class AuthController extends Controller
{
    public function create(Request $request){
        $response = null;
        try{
            $aUser = Auth::user();
            if (!$aUser) {
                throw new UnauthorizedUserException('Usuario no autenticado');
            }

            $validator = Validator::make($request->all(), [
                'name' => 'required|min:4',
                'email' => 'required|email|unique:users',
                'password' => 'required|min:8|max:16',
      
            ]);
            
           if ($validator->fails()) {
                $errorMessages = $validator->errors()->all();
                $errorMessage = implode(', ', $errorMessages);
                throw new InvalidUsersException($errorMessage);
            }
            
            
            //crea el usario $user = 
           User::create([
                'name'=> $request->name,
                'email'=> $request->email,
                'password'=> $request->password,
            ]);

            date_default_timezone_set('America/Mexico_City');
            $name = $request->name;
            $email = $request->email;
    

            $responseData = [
                'status'=> true,
                'message'=> 'Usuario Creado Correctamente',
            ];
    
            $response = response()->json($responseData, 200);

        }catch (InvalidUsersException $e) {
            $response = $this->handleError('No se cumple con todos los requisitos', $e, 422);
        }catch (UnauthorizedUserException $e) {
            $response = $this->handleError('Usuario no autenticado', $e, 401);
        }catch (\Exception $e) {
            $response = $this->handleError('Ha ocurrido un error al crear la cuenta', $e, 500);
        }
        return $response;
    }

    public function login(Request $request){
        $response = null;
        try{
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required',
            ]);
    
            if ($validator->fails()) {
                $errorMessages = $validator->errors()->all();
                $errorMessage = implode(', ', $errorMessages);
                throw new InvalidCredentialsException($errorMessage);
            }

            if(!Auth::attempt($request->only('email','password'))){
                return response()->json([
                    'status'=> false,
                    'message' => 'Verifica el correo o contraseña',
                    'errors'=> ['No Autorizado']
                ],401);
            }
            $user = User::where('email',$request->email)->first();
            
            $expiration = Carbon::now()->addMinutes(config('sanctum.expiration'));
            //'data'=>$user,
            $responseData = [
                'status'=> true,
                'message'=> 'Usuario Logeado Correctamente',
                'data' => [
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'token'=> $user->createToken('API TOKEN')->plainTextToken,
                'expires_at' => $expiration->toDateTimeString(),
            ];
    
            $response = response()->json($responseData, 200);
        }catch (InvalidCredentialsException $e) {
            $response = $this->handleError('Datos invalidos', $e, 422);
        }catch (\Exception $e) {
            $response = $this->handleError('Ha ocurrido un error', $e, 500);
        }
        return $response;
    } 

    public function logout(){
        $response = null;
        try{
            $user = Auth::user();
            if($user){
                if ($user instanceof User) {
                    $user->tokens()->delete();
                }

                $responseData = [
                    'status'=> true,
                    'message'=> 'Usuario Cerro Sesión Correctamente',
                ];
        
                $response = response()->json($responseData, 200);
            }
        }catch (\Exception $e) {
            $response = $this->handleError('Ha ocurrido un error ', $e, 500);
        }
       return $response;
    }

    private function handleError($message, $exception, $statusCode) {
        return response()->json([
            'status' => false,
            'message' => $message,
            'error' => $exception->getMessage()
        ], $statusCode);
    }
}
