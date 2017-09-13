<?php

namespace Craft\AcclaroTranslations;

use Pimple\Container as Pimple;
use Closure;
use CApplication as Craft;

class Container extends Pimple
{
    public function __construct(Craft $craft)
    {
        parent::__construct();

        $this[Craft::class] = $this->factory(function ($c) use ($craft) {
            return $craft;
        });

        $this[UrlHelper::class] = $this->factory(function ($c) {
            return new UrlHelper();
        });

        $this[UrlGenerator::class] = $this->factory(function ($c) {
            return new UrlGenerator(
                $c[Craft::class],
                $c[UrlHelper::class]
            );
        });

        $this[Translator::class] = $this->factory(function ($c) {
            return new Translator();
        });

        $this[ElementCloner::class] = $this->factory(function ($c) {
            return new ElementCloner(
                $c[Craft::class]
            );
        });

        $this[Repository\TranslationRepository::class] = $this->factory(function ($c) {
            return new Repository\TranslationRepository(
                $c[Craft::class]
            );
        });

        $this[Repository\CategoryRepository::class] = $this->factory(function ($c) {
            return new Repository\CategoryRepository(
                $c[Craft::class]
            );
        });

        $this[Repository\TagRepository::class] = $this->factory(function ($c) {
            return new Repository\TagRepository(
                $c[Craft::class]
            );
        });

        $this[Repository\DraftRepository::class] = $this->factory(function ($c) {
            return new Repository\DraftRepository(
                $c[Craft::class]
            );
        });

        $this[Repository\EntryRepository::class] = $this->factory(function ($c) {
            return new Repository\EntryRepository(
                $c[Craft::class]
            );
        });

        $this[Repository\FileRepository::class] = $this->factory(function ($c) {
            return new Repository\FileRepository(
                $c[Craft::class]
            );
        });

        $this[Repository\GlobalSetRepository::class] = $this->factory(function ($c) {
            return new Repository\GlobalSetRepository(
                $c[Craft::class]
            );
        });

        $this[Repository\GlobalSetDraftRepository::class] = $this->factory(function ($c) {
            return new Repository\GlobalSetDraftRepository(
                $c[Craft::class],
                $c[Translator::class]
            );
        });

        $this[Repository\LanguageRepository::class] = $this->factory(function ($c) {
            return new Repository\LanguageRepository(
                $c[Craft::class]
            );
        });

        $this[Repository\OrderRepository::class] = $this->factory(function ($c) {
            return new Repository\OrderRepository(
                $c[Craft::class]
            );
        });

        $this[Repository\TranslatorRepository::class] = $this->factory(function ($c) {
            return new Repository\TranslatorRepository(
                $c[Craft::class]
            );
        });

        $this[Repository\UserRepository::class] = $this->factory(function ($c) {
            return new Repository\UserRepository(
                $c[Craft::class]
            );
        });

        $this[WordCounter::class] = $this->factory(function ($c) {
            return new WordCounter();
        });

        $this[FieldTranslator\Factory::class] = $this->factory(function ($c) {
            return new FieldTranslator\Factory(
                $c[Craft::class],
                $c[ElementCloner::class],
                $c[Repository\TranslationRepository::class],
                $c[Repository\CategoryRepository::class],
                $c[Repository\TagRepository::class],
                $c[WordCounter::class]
            );
        });

        $this[Job\Factory::class] = $this->factory(function ($c) {
            return new Job\Factory(
                $c[Craft::class],
                $c[ElementTranslator::class],
                $c[Repository\DraftRepository::class],
                $c[Repository\OrderRepository::class],
                $c[Repository\FileRepository::class],
                $c[Repository\EntryRepository::class],
                $c[Repository\GlobalSetRepository::class],
                $c[Repository\GlobalSetDraftRepository::class],
                $c[TranslationService\Factory::class]
            );
        });

        $this[TranslationService\Factory::class] = $this->factory(function ($c) {
            return new TranslationService\Factory(
                $c[Craft::class],
                $c[ElementTranslator::class],
                $c[Repository\DraftRepository::class],
                $c[Repository\EntryRepository::class],
                $c[Repository\GlobalSetRepository::class],
                $c[Repository\GlobalSetDraftRepository::class],
                $c[Repository\LanguageRepository::class],
                $c[UrlGenerator::class],
                $c[Translator::class]
            );
        });

        $this[ElementTranslator::class] = $this->factory(function ($c) {
            return new ElementTranslator(
                $c[Craft::class],
                $c[FieldTranslator\Factory::class],
                $c[WordCounter::class]
            );
        });

        $this[ElementToXmlConverter::class] = $this->factory(function ($c) {
            return new ElementToXmlConverter(
                $c[Craft::class],
                $c[ElementTranslator::class],
                $c[UrlGenerator::class]
            );
        });

        $this[OrderSearchParams::class] = $this->factory(function ($c) {
            return new OrderSearchParams(
                $c[Craft::class],
                $c[Repository\LanguageRepository::class],
                $c[Repository\OrderRepository::class],
                $c[Translator::class]
            );
        });
    }
}
