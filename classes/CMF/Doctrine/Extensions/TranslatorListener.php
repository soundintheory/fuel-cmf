<?php

namespace CMF\Doctrine\Extensions;

use Doctrine\Common\EventArgs;
use Doctrine\ORM\ORMInvalidArgumentException;
use Gedmo\Tool\Wrapper\AbstractWrapper;
use Gedmo\Mapping\MappedEventSubscriber;


/**
 * The translation listener handles the generation and
 * loading of translations for entities which implements
 * the Translatable interface.
 *
 * This behavior can impact the performance of your application
 * since it does an additional query for each field to translate.
 *
 * Nevertheless the annotation metadata is properly cached and
 * it is not a big overhead to lookup all entity annotations since
 * the caching is activated for metadata
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TranslatorListener extends MappedEventSubscriber
{

    public $toProcess;
    public $toRemove;

    private $translateFrom;
    private $translateTo = array();

    private $inProgress = false;
    private $isInPostFlush = false;

    /**
     * Specifies the list of events to listen
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
            'postFlush',
            'onFlush',
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function getNamespace()
    {
        return __NAMESPACE__;
    }

    private function process($em){
        foreach($this->translateTo as $aLang){
            foreach($this->toProcess as $aModel){
                if($this->isModelBase($aModel)){
                    $this->current_model = $aModel->metadata()->name;

                    $translatableFields = \CMF\ADMIN::getTranslatable($this->current_model);
                    if($translatableFields) {
                        $aModel = $this->translateFields($aModel, $translatableFields, $aLang->code);
                    }
                    \CMF\Doctrine\Extensions\Translatable::setLang($aLang->code);

                    $em->persist($aModel);
                    $em->flush($aModel);

                }
            }
        }
    }

    private function isModelBase($model)
    {
        return $model instanceof \CMF\Model\Base;
    }

    private function translateFields($model,$fields,$langCode){

        $apiKey = 'AIzaSyAe_BkRQcb1ykcO6f19dKoEoX57KotlJJs';
        $url = 'https://www.googleapis.com/language/translate/v2?key=' . $apiKey;

        foreach ($fields as $aField) {
            $url .= '&q=' . urlencode($model->$aField);
        }
        $url .= '&source=' . $this->translateFrom->code . '&target=' . $langCode;


        $handle = curl_init($url);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($handle);

        if (FALSE === $response)
            throw new \Exception(curl_error($handle), curl_errno($handle));

        $responseDecoded = json_decode($response, true);
        curl_close($handle);

        $pos = 0;
        foreach($fields as $aField){
            $model->set($aField , $responseDecoded['data']['translations'][$pos]['translatedText']);
            $pos ++;
        }
        return $model;
    }


    /**
     * Processes the entities that were marked during onFlush
     */
    public function postFlush(EventArgs $args)
    {
        if($this->isInPostFlush) return;

        $this->isInPostFlush = true;

       if(count($this->translateTo )){
            $em = \D::manager();

            $this->process($em);

            $this->toProcess = array();
            $this->toRemove = array();
       }

        $this->isInPostFlush = false;
        $this->inProgress = false;
    }

    /**
     * Gets the entities scheduled for insert / update / delete and flags them for processing
     */
    public function onFlush(EventArgs $eventArgs)
    {
        if($this->inProgress) return;

        $this->inProgress = true;

        $this->translateFrom = \CMF\Model\Language::select('item')->where('item.code = :lang')->setParameter('lang',\Config::get('language', 'zh'))->getQuery()->getResult()[0];

        $this->translateTo = \CMF\Model\Language::select('item')->where('item.update_from = :from')->setParameter('from',$this->translateFrom->id)->getQuery()->getResult();


        $this->toProcess = array();
        $this->toRemove = array();

        if(count($this->translateTo )) {
            $em = \D::manager();
            $uow = $em->getUnitOfWork();

            foreach ($uow->getScheduledEntityInsertions() as $object) {
                $this->toProcess[spl_object_hash($object)] = $object;
            }
            foreach ($uow->getScheduledEntityUpdates() as $object) {
                $this->toProcess[spl_object_hash($object)] = $object;
            }
        }
    }

}

