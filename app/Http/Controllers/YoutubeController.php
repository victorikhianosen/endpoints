<?php

namespace App\Http\Controllers;

use App\Http\Requests\YoutubeDownloadRequest;
use App\Http\Requests\YoutubeResolutionRequest;
use App\Services\YoutubeService;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class YoutubeController extends Controller
{


    use HttpResponses;

    private $youtubeService;

    public function __construct(YoutubeService $youtubeService)
    {
        $this->youtubeService = $youtubeService;
    }

    public function getResolutions(YoutubeDownloadRequest $request)
    {
        return $this->youtubeService->getResolutions($request->url);
    }


    public function downloadWithResolution(YoutubeResolutionRequest $request)
    {
        $validated = $request->validated();
        return $this->youtubeService->downloadWithResolution($validated);
    }


    public function download(YoutubeDownloadRequest $request)
    {
        $validated = $request->validated();
        return $this->youtubeService->download($validated);
    }







}
