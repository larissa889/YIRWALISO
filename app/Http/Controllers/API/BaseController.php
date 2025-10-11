<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BaseController extends Controller
{
    /**
     * Success response method.
     *
     * @param mixed $result
     * @param string $message
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendResponse($result, $message, $code = 200)
    {
        $response = [
            'success' => true,
            'data' => $result,
            'message' => $message,
        ];

        return response()->json($response, $code);
    }

    /**
     * Error response method.
     *
     * @param string $error
     * @param array $errorMessages
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendError($error, $errorMessages = [], $code = 404)
    {
        $response = [
            'success' => false,
            'message' => $error,
        ];

        if (!empty($errorMessages)) {
            $response['errors'] = $errorMessages;
        }

        return response()->json($response, $code);
    }

    /**
     * Return validation error response.
     *
     * @param \Illuminate\Contracts\Validation\Validator $validator
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendValidationError($validator)
    {
        return $this->sendError(
            'Validation Error.',
            $validator->errors(),
            422
        );
    }

    /**
     * Return unauthorized error response.
     *
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendUnauthorized($message = 'Unauthorized')
    {
        return $this->sendError($message, [], 401);
    }

    /**
     * Return forbidden error response.
     *
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendForbidden($message = 'Forbidden')
    {
        return $this->sendError($message, [], 403);
    }

    /**
     * Return not found error response.
     *
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendNotFound($message = 'Resource not found')
    {
        return $this->sendError($message, [], 404);
    }

    /**
     * Return server error response.
     *
     * @param string $message
     * @param array $errors
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendServerError($message = 'Internal Server Error', $errors = [])
    {
        return $this->sendError($message, $errors, 500);
    }

    /**
     * Return paginated response.
     *
     * @param mixed $paginatedData
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendPaginated($paginatedData, $message = 'Data retrieved successfully')
    {
        $response = [
            'success' => true,
            'data' => $paginatedData->items(),
            'pagination' => [
                'total' => $paginatedData->total(),
                'per_page' => $paginatedData->perPage(),
                'current_page' => $paginatedData->currentPage(),
                'last_page' => $paginatedData->lastPage(),
                'from' => $paginatedData->firstItem(),
                'to' => $paginatedData->lastItem()
            ],
            'message' => $message,
        ];

        return response()->json($response, 200);
    }
}
