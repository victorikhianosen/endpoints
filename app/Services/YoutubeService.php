<?php

namespace App\Services;

use GuzzleHttp\Client;
use App\Traits\HttpResponses;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class YoutubeService
{
    // Implement YouTube related logic here

    use HttpResponses;


    public function getResolutions($url)
    {
        $ytDlpPath = "yt-dlp";

        // Step 1: Get the video title
        $titleCommand = "$ytDlpPath --get-title \"$url\"";
        Log::info("=========================");
        Log::info("Check Available Qualities API");
        Log::info("Executing yt-dlp title command: $titleCommand");

        $processTitle = proc_open($titleCommand, [
            1 => ['pipe', 'w'],  // stdout
            2 => ['pipe', 'w'],  // stderr
        ], $pipesTitle);

        $titleOutput = stream_get_contents($pipesTitle[1]);
        fclose($pipesTitle[1]);
        proc_close($processTitle);

        if (empty($titleOutput)) {
            Log::error("yt-dlp title command failed for URL: $url");
            return response()->json(['error' => 'Unable to fetch video title'], 500);
        }

        $videoTitle = trim($titleOutput);
        Log::info("Video title fetched: $videoTitle");

        // Step 2: Get available formats in JSON
        $jsonCommand = "$ytDlpPath -j \"$url\"";
        Log::info("Executing yt-dlp JSON format command: $jsonCommand");

        $processJson = proc_open($jsonCommand, [
            1 => ['pipe', 'w'],  // stdout
            2 => ['pipe', 'w'],  // stderr
        ], $pipesJson);

        $jsonOutput = stream_get_contents($pipesJson[1]);
        fclose($pipesJson[1]);
        proc_close($processJson);

        if (empty($jsonOutput)) {
            Log::error("yt-dlp json command failed for URL: $url");
            return response()->json(['error' => 'Unable to fetch video formats'], 500);
        }

        $json = json_decode($jsonOutput, true);
        $formats = $json['formats'] ?? [];

        $result = [];
        $seenResolutions = [];

        foreach ($formats as $format) {
            if (!isset($format['resolution']) || !isset($format['format_id']) || !isset($format['ext']) || !isset($format['filesize'])) {
                continue;
            }

            $resolution = $format['resolution'];
            $size = $format['filesize'];

            if (!isset($seenResolutions[$resolution]) || $size < $seenResolutions[$resolution]['filesize']) {
                $seenResolutions[$resolution] = [
                    'title' => $videoTitle,
                    'format_code' => $format['format_id'],
                    'resolution' => $resolution,
                    'extension' => $format['ext'],
                    'description' => round($size / 1024 / 1024, 2) . 'MiB, ' . $format['ext'],
                    'filesize' => $size,
                    'url' => $url
                ];
            }
        }

        // Remove 'filesize' key before sending response
        $finalData = array_map(function ($item) {
            unset($item['filesize']);
            return $item;
        }, array_values($seenResolutions));

        return response()->json([
            'status' => 'success',
            'message' => 'Video qualities fetched successfully.',
            'data' => $finalData
        ]);
    }



    public function downloadWithResolution($validated)
{
    $url = $validated['url'];
    $resolution = $validated['resolution'];

    $format = $this->getYtDlpFormat($resolution); // Example: "bestvideo[height<=720]+bestaudio"

    Log::info("=========================");
    Log::info("Download With Resolution API");
    Log::info("Using yt-dlp format: " . $format);

    $ytDlpPath = '/home/victor/.local/bin/yt-dlp';
    $escapedUrl = escapeshellarg($url);
    $escapedFormat = escapeshellarg($format);

    // Step 1: Get video title
    $titleCommand = "$ytDlpPath --get-title $escapedUrl";
    $processTitle = proc_open($titleCommand, [
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ], $pipesTitle);

    if (!is_resource($processTitle)) {
        return response()->json(['error' => 'Unable to fetch video title.'], 500);
    }

    $titleOutput = stream_get_contents($pipesTitle[1]);
    fclose($pipesTitle[1]);
    proc_close($processTitle);
    $videoTitle = trim($titleOutput);

    // Step 2: Download and merge video+audio directly using yt-dlp
    $filename = 'video_' . time() . '.mp4';
    $outputPath = storage_path("app/public/youtube/{$filename}");

    // ✅ ADD merge-output-format
    $downloadCommand = "$ytDlpPath -f $escapedFormat --merge-output-format mp4 -o " . escapeshellarg($outputPath) . " $escapedUrl";
    exec($downloadCommand, $output, $exitCode);

    if ($exitCode !== 0 || !file_exists($outputPath)) {
        Log::error("Download failed or file not found: $outputPath");
        return response()->json(['error' => 'Unable to download or merge video.'], 500);
    }

    $videoUrl = Storage::url("youtube/{$filename}");

    return $this->success('Video downloaded successfully', [
        'video' => $videoUrl,
        'title' => $videoTitle
    ]);
}



    private function getYtDlpFormat($resolution)
    {
        switch ($resolution) {
            // case '256x144':
            //     return 'bestaudio[height<=144]+bestvideo[height<=144]/best[height<=144]';
            case '426x240':
                return 'bestaudio[height<=240]+bestvideo[height<=240]/best[height<=240]';
            case '640x360':
                return 'bestaudio[height<=360]+bestvideo[height<=360]/best[height<=360]';
            case '1280x720':
                return 'bestaudio[height<=720]+bestvideo[height<=720]/best[height<=720]';
            case '1920x1080':
                return 'bestaudio[height<=1080]+bestvideo[height<=1080]/best[height<=1080]';
            default:
                return 'best';
        }
    }
}
