<?php namespace SnapsterApi;

class Snapster
{
    protected $vk_token = '';
    protected $client;

    protected $auth_url = 'https://snapster.io/vkauth';
    protected $api_url = 'https://snapster.io/snapster-web.php';

    protected $last_result = [];
    protected $room_id = 0;

    public function __construct()
    {
        $this->client = new HttpClient();
    }

    public function setToken($vk_token)
    {
        $this->vk_token = $vk_token;
    }

    public function login()
    {
        $to_login = $this->auth_url.'#access_token='.$this->vk_token;
        $this->client->get($to_login);
        $result = $this->request([
                "act" => "update_header",
                "token" => $this->vk_token,
                "_rnd" => time()
            ]);
        $this->last_result = $result;
        //var_dump($result);
    }

    public function request($data)
    {
        $response = $this->client->post($this->api_url, $data);
        $json = json_decode($response, false);
        return $json;
    }

    public function getUploadUrl()
    {
        $result = $this->request([
                "act" => "rooms_list",
                "tab" => "result",
                "token" => $this->vk_token,
                "_rnd" => time()
            ]);
        $this->room_id = $result->response->room_info->id;
        return $result->response->room_info->uploadServer;
    }

    public function uploadPhoto($photo_url)
    {
        $photo = $this->client->get($photo_url);
        $filename = __DIR__."/".mt_rand(1, 1000000).".jpg";
        file_put_contents($filename, $photo);
        $params = [
            "file1" => "@".$filename
        ];
        $result = $this->client->post($this->getUploadUrl(), $params);
        unlink($filename);
        $data = json_decode($result, false);
        //var_dump($data);
        return $data;
    }

    public function savePhoto($photo_uploaded, $description)
    {
        $params = [
            "act" => "save_photo",
            "descr" => $description,
            "vk_export" => 0,
            "vk_export_method" => 1,
            "room_id" => $this->room_id,
            "_rnd" => time()
        ];
        $params = array_merge($params, (array) $photo_uploaded);
        $result = $this->request($params);
        if ($result->success == 1) {
            return true;
        } else {
            return false;
        }
    }

    public function test()
    {
    }
}