<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\httpclient\Client; // Import Yii HTTP client

class WebhookController extends Controller
{
    public $enableCsrfValidation = false; // Disable CSRF validation for webhooks

    public function actionTeletypeWebhook()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $postData = Yii::$app->request->post();

        $name = $postData['name'];
        $payload = json_decode($postData['payload'], true);

        $createTime = $payload['message']['createdAt']['date'];
        $text = $payload['message']['text'];
        $dialogId = $payload['message']['dialogId'];

        Yii::info($text, "webhook");
        if($name === "success send") $this->saveTextToFile('@runtime/logs/Operators.log', "Created: ".$createTime."\nMessage:".$text."\n");
        else if($name == "new message") {
            $this->saveTextToFile('@runtime/logs/Clients.log', "Created: ".$createTime."\nMessage:".$text."\n");
            if (strpos($text, 'ping?') !== false) {
                $this->sendHttpRequest($dialogId, "pong!");
            }
        }

        return [
            'status' => 'success',
            'text' => $text
            // 'text' => $text,
        ];
    }

    private function saveTextToFile($path, $text)
    {
        $filePath = Yii::getAlias($path);

        $file = fopen($filePath, 'a');

        fwrite($file, $text . PHP_EOL);

        fclose($file);
    }

    private function sendHttpRequest($conversationId, $text)
    {
        $client = new Client();

        $url = 'https://api.teletype.app/public/api/v1/message/send';

        $params = [
            'dialogId' => $conversationId,
            'text' => $text
        ];

        $headers = [
            'Content-Type' => 'application/json',
            'X-Auth-Token' => 'KHsQTQTeYwIHitt9RJrZ8tRHh13s_ideuF1CmxrD23lWk817rV9xm0BonQj0X57l'
        ];

        // Send a POST request
        $response = $client->createRequest()
        ->setMethod('POST')
        ->setUrl($url)
        ->setData($params)
        ->addHeaders($headers)
        ->send();

        // Log the response for debugging
        Yii::info('HTTP Request Response: ' . print_r($response->getData(), true), __METHOD__);

        // Check if the request was successful
        if ($response->isOk) {
            Yii::info('HTTP Request was successful', __METHOD__);
        } else {
            Yii::error('HTTP Request failed: ' . $response->statusCode, __METHOD__);
        }
    }
}
