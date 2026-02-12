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

    /**
     * Respond depending on request preference or explicit format.
     * - format can be: null|'json'|'view'|'plain'
     * - if format is null, we detect by: query param `format`, wantsJson(), ajax(), or `api/*` path
     * - $view may be null when not returning a blade view
     */
    public function respondWith($request, $view = null, array $data = [], $plain = null, $format = null)
    {
        // determine format
        if (! $format) {
            if ($request->query('format')) {
                $format = $request->query('format');
            } elseif (
                $request->wantsJson() ||
                $request->ajax() ||
                str_contains($request->header('Accept', ''), 'application/json') ||
                str_contains(strtolower($request->header('User-Agent', '')), 'postman') ||
                $request->header('X-Requested-With') === 'XMLHttpRequest'
            ) {
                $format = 'json';
            } else {
                $format = 'view';
            }
        }

        if ($format === 'json') {
            return response()->json($data);
        }

        if ($format === 'plain') {
            $body = $plain ?? json_encode($data);
            return response($body, 200)->header('Content-Type', 'text/plain');
        }

        // view (blade)
        if ($view) {
            return $this->render($view, $data);
        }

        // fallback to json
        return response()->json($data);
    }
}