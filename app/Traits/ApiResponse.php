<?php


namespace App\Traits;

use App\Library\QueryPaginator;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Response;
use Illuminate\Validation\Validator;

trait ApiResponse
{
    /**
     * @param array $data
     * @param string $message
     * @param string $requestId
     * @param int $httpCode
     * @return Application|ResponseFactory|Response
     */
    protected function success($data = [], $message = '', $httpCode = 200, $requestId = '')
    {
        $requestId = $requestId ? $requestId : app('Pid');
        return response([
            'success' => true,
            'data' => $data,
            'message' => $message,
            'request_id' => $requestId,
            'error_code' => 0,
        ], $httpCode);
    }

    /**
     * @param string $message
     * @param array $data
     * @param string $errorCode
     * @param string $requestId
     * @param int $httpCode
     * @return Application|ResponseFactory|Response
     */
    protected function error($message = '', $data = [], $errorCode = '', $httpCode = 400, $requestId = '')
    {
        $requestId = $requestId ? $requestId : app('Pid');
        return response([
            'success' => false,
            'data' => $data,
            'message' => $message,
            'request_id' => $requestId,
            'error_code' => $errorCode,
        ], $httpCode);
    }

    /**
     * @param Validator|\Illuminate\Contracts\Validation\Validator $validator
     * @param array $data
     * @param string $errorCode
     * @param string $requestId
     * @param int $httpCode
     * @return ResponseFactory|Response
     */
    protected function errorWithValidator(Validator $validator, $data = [], $errorCode = '', $httpCode = 400, $requestId = '')
    {
        $errors = data_get($validator->getMessageBag()->messages(), '*.0');
        return $this->error($errors, $data, $errorCode, $httpCode, $requestId);
    }

    /**
     *
     * @param QueryPaginator $paginator
     * @param string $message
     * @param string $requestId
     * @param int $httpCode
     * @return ResponseFactory|Response
     */

    protected function successWithPaginator(QueryPaginator $paginator, $message = '', $httpCode = 200, $requestId = '')
    {
        if (!$paginator) return $this->success();
        $requestId = $requestId ? $requestId : app('Pid');

        $data = $paginator->getData();
        return response([
            'success' => true,
            'data' => $data,
            'message' => $message,
            'request_id' => $requestId,
            'error_code' => '',

            'pagination' => [
                'page' => $paginator->getPage(),
                'limit' => $paginator->getLimit(),
                'item_count' => $paginator->getItemCount(),
                'page_count' => $paginator->getPageCount(),
            ]
        ], $httpCode);
    }

    /**
     * @param $data
     * @return Application|ResponseFactory|Response
     */
    protected function successWithPaginatorCache($data)
    {
        return response($data, 200);
    }


    protected function getRequestInput(array $mapping = [], array $casts = [], $input = null)
    {
        if ($input === null) {
            $request = app('request');
            $input = $request->all();
        }
        foreach ($input as $fromField => $value) {
            if (array_key_exists($fromField, $mapping)) {
                $toField = $mapping[$fromField];
                $input[$toField] = $value;
                unset($input[$fromField]);
            }
        }
        if (!empty($input['order_by']) && array_key_exists($input['order_by'], $mapping)) {
            $input['order_by'] = $mapping[$input['order_by']];
        }
        return $input;
    }
}
