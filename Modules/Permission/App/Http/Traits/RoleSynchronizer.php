<?php

namespace Modules\Permission\App\Http\Traits;

use ErrorException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Route;
use Modules\Permission\App\Models\Permission;
use Nwidart\Modules\Facades\Module;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use TypeError;

trait RoleSynchronizer
{

	public static function getAllApiRoutes(): array
	{
		return array_filter(collect(Route::getRoutes())->map(function (\Illuminate\Routing\Route $route) {
			try {
				if (!in_array('auth:web', $route->middleware())) return null;
				if ($route->getController()){
					# Or use route namespaces! more easily
					$nameSpace  = get_class($route->getController());
					$module		= explode('\\', $nameSpace)[1];
					if (is_null($route->getName())){
						throw new RouteNotFoundException();
					}
					if ($module == 'Fortify') return null;
					return [
						'module'	=> $module ?? null,
						'name' 		=> $route->getName(),
						'method' 	=> $route->methods(),
						'url'		=> $route->uri()
					];
				}
				return null;
			} catch (RouteNotFoundException $exception){
				# Yeah, I know.
//				throw new RouteNotFoundException("نام گذاری روت {$route->uri()} به درستی صورت نگرفته است.");
			} catch (TypeError $exception){
				# I don't found better way to handle error, if you have any idea JUST DO IT and be in touch with me > Sahandi81 `EverySocialMedia`
				return [
						'module'	=> null,
						'name' 		=> $route->getName(),
						'method' 	=> $route->methods(),
						'url'		=> $route->uri()
				];
			}

		})->toArray(), function ($value){
			return !is_null($value);
		});
	}

	public static function getAllPermissionRoutes(): array
	{
		return DB::table((new Permission())->getTable())->pluck('route_name')->toArray();
	}

	public static function sync(): array
	{
		$successCounter = 0;
		$failedCounter 	= [];
		$apiRoutes = self::getAllApiRoutes();
		$dbPermissionRoutes = self::getAllPermissionRoutes();
		$translateRouteNames = self::getModulesName();

		# If you update any route. need to change this code or delete the record.
		foreach ($apiRoutes as $apiRoute) {
			if (!in_array($apiRoute['name'], $dbPermissionRoutes)){
				if (isset($apiRoute['name'])){
					DB::table((new Permission())->getTable())->insert([
							'route_name'=> $apiRoute['name'],
							'title'		=> $apiRoute['name'] ? ($translateRouteNames[$apiRoute['name']] ?? $apiRoute['name']) : 'CHECK_NAMES',
							'parent'	=> $apiRoute['module'] ?? 'other',
							'url'		=> $apiRoute['url'],
							'method'	=> $apiRoute['method'][0] ?? null,
					]);
					$successCounter++;
//				}else{
//					$failedCounter[] = $apiRoute;
				}
			}
		}
		$details = ['failed' => $failedCounter, 'success' => $successCounter];
		if ($successCounter == 0){
			# check routes that must pass admin_access middleware
			return (['status' => false, 'msg' => 'NO_NEW_ROUTE_FOUND', 'details' => $details]);
		}
		return (['status' => true, 'msg' => 'NEW_ROUTES_ADDED', 'details' => $details]);
	}

	/**
	 * @throws ErrorException
	 */
	protected static function getModulesName(): array
	{
		$modules = Module::all();
		$result = [];
		foreach ($modules as $module) {
			$moduleName = $module->getName();
			try {
				# Get `routes.php` in every resources/lang directory.
				 $routeTranslates = include base_path('Modules/'.$moduleName.'/Resources/lang/'. Lang::locale() .'/routes.php');
				 $result = array_merge($result, $routeTranslates);
			}catch (ErrorException $e){
//				throw new ErrorException('ROUTES_LANG_DOES_NOT_EXISTS');
			}
		}
		return $result;
	}
}