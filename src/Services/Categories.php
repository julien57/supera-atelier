<?php

namespace App\Services;

use App\Repository\EventTypeRepository;

class Categories
{
    /**
     * @var EventTypeRepository
     */
    private $eventTypeRepository;

    public function __construct(EventTypeRepository $eventTypeRepository)
    {
        $this->eventTypeRepository = $eventTypeRepository;
    }

    public function getAllCategories()
    {
        return $this->eventTypeRepository->findAll();
    }
}