<?php

// src/Service/ZoteroApiService.php
namespace App\Service;

class ZoteroApiService
{
    protected $options;

    public function __construct(array $options)
    {
        $this->options = $options;
    }

    public function getGroupId()
    {
        return $this->options['group-id'];
    }

    public function getInstance($groupId = null)
    {
        $options = $this->options;

        if (!is_null($groupId)) {
            $options['group-id'] = $groupId;
        }

        $api = new \Hedii\ZoteroApi\ZoteroApi($options['api-key']);

        if (!empty($options['group-id'])) {
            $api->group($options['group-id']);
        }

        return $api;
    }
}
