<?php

namespace Rubot\Utils;

class Limiter
{
    private string $file;
    private int $limit;
    private int $baseInterval;
    private int $maxExtraInterval;
    private array $data = [];

    public function __construct(
        string $file = "limit.json",
        int $limit = 10,
        int $baseInterval = 60,
        int $maxExtraInterval = 600
    ) {
        $this->file = $file;
        $this->limit = $limit;
        $this->baseInterval = $baseInterval;
        $this->maxExtraInterval = $maxExtraInterval;

        if (file_exists($file)) {
            $this->data = json_decode(file_get_contents($file), true) ?? [];
        } else {
            if (dirname($file)) {
                mkdir(dirname($file));
            }
        }
    }

    /**
     * check user requets
     * @param string $chat_id user chat id
     * @param int $incrementPerBlock 
     * @return array|array {allowed: bool, currentInterval: int, remaining: int, sendMessage: bool}
     */
    public function check(string $chat_id, ?int $incrementPerBlock = null): array
    {
        $now = time();
        $incrementPerBlock ??= $this->baseInterval;

        if (!isset($this->data[$chat_id])) {
            $this->data[$chat_id] = [
                "count" => 1,
                "start" => $now,
                "extra" => 0,
                "sendMessage" => false
            ];
            $this->save();
            return $this->allowedResponse($this->limit - 1);
        }

        $user = &$this->data[$chat_id];
        $currentInterval = $this->baseInterval + $user["extra"];

        if ($now - $user["start"] > $currentInterval) {
            $user["count"] = 1;
            $user["start"] = $now;
            $user["extra"] = max(0, $user["extra"]);
            $user["sendMessage"] = false;
            $this->save();
            return $this->allowedResponse($this->limit - 1, $user["extra"]);
        }


        if ($user["count"] >= $this->limit) {
            $sendMessage = !$user["sendMessage"];

            if (!$user["sendMessage"]) {
                echo "hell";
                $user["extra"] = min($user["extra"] + $incrementPerBlock, $this->maxExtraInterval);
            }
            $user["sendMessage"] = true;
            $this->save();

            return [
                "allowed" => false,
                "remaining" => 0,
                "sendMessage" => $sendMessage,
                "currentInterval" => $currentInterval
            ];
        }



        $user["count"]++;
        $this->save();

        return $this->allowedResponse($this->limit - $user["count"], $user["extra"]);
    }

    private function allowedResponse(int $remaining, int $extra = 0): array
    {
        return [
            "allowed" => true,
            "remaining" => $remaining,
            "sendMessage" => false,
            "currentInterval" => $this->baseInterval + $extra
        ];
    }

    public function resetUser(string $chat_id): void
    {
        unset($this->data[$chat_id]);
        $this->save();
    }

    private function save(): void
    {
        file_put_contents($this->file, json_encode($this->data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        touch($this->file);
    }

    public function getUser(string $chat_id): ?array
    {
        return $this->data[$chat_id] ?? null;
    }
}