<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Repositories\MaterielRepository;

class MaterielController
{
    private MaterielRepository $materielRepository;

    public function __construct(MaterielRepository $materielRepository)
    {
        $this->materielRepository = $materielRepository;
    }

    /**
     * Liste de tout le matériel (protégé)
     */
    public function index(Request $request): Response
    {
        $materiels = $this->materielRepository->findAll();
        
        return (new Response())
            ->setStatusCode(Response::HTTP_OK)
            ->setJsonContent($materiels);
    }
}
