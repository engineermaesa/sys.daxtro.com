<?php 

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Log;

class Controller extends BaseController
{
    public $pageTitle;
    public $breadCrumbs = [];
    public $user;
  
    const INTERNAL_SERVER_ERROR_MESSAGE = 'An unexpected error occurred. Please try again later.';

    public function __construct()
    {
        $this->user = [];

        if (Auth::check()) {
            $this->user = Auth::user();
        }
    }

    /**
     * Render the view with the given data.
     *
     * @param string $view The name of the view to render.
     * @param array $data The data to pass to the view.
     * @return \Illuminate\View\View The rendered view.
     */
    public function render($view, $data = []) {
        $user = Auth::user();
        
        $data['page_title']     = $this->pageTitle ?? 'PSSI';
        $data['bread_crumbs']   = $this->breadCrumbs;
        $data['assets_version'] = env('ASSETS_VERSION', '1.0.0');

        if ($user) {
            $data['user'] = $user;            
        }

        return view($view, $data);
    }

    
    public function setJsonResponse($message, $data = [], $status = 200, $errors = null) {
        $result = [
            'message' => $message
        ];

        if ($data) {
            $result['data'] = $data;
        }

        if ($status > 200 && ! empty($errors)) {
            $logData = [
                'status'  => $status,
                'message' => $errors->getMessage(),
                'url'     => request()->fullUrl(),
                'payload' => request()->all(),
                'result'  => $result,
                'errors'  => $errors,
            ];
    
            Log::channel('custom_error')->error($errors->getMessage(), $logData);
        }

        return response()->json($result, $status);
    }
}