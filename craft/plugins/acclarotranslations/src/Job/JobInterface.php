<?php

namespace Craft\AcclaroTranslations\Job;

interface JobInterface
{
    /**
     * @return mixed
     */
    public function handle();
}