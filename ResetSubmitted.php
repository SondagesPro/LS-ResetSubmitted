<?php
/**
 * Plugin for limesurvey : reset submitted date when reload survey
 *
 * @author Denis Chenu <denis@sondages.pro>
 * @copyright 2021 Denis Chenu <http://www.sondages.pro>
 * @license GPL v3
 * @version 1.0.0
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */
class ResetSubmitted extends PluginBase
{
    protected $storage = 'DbStorage';

    protected static $description = "With token answer persistence : reset submitted date when reloading a previously submitted response.";
    protected static $name = 'ResetSubmitted';

    /**
     * @var array[] the settings
     */
    protected $settings = array(
        'active' => array(
            'type' => 'checkbox',
            'htmlOptions' => array(
                'value' => 1,
                'uncheckValue' => 0,
            ),
            'label' => "Reset submitted date when reload an already reloaded reponse.",
            'default' => 0,
        )
    );

    /** @inheritdoc **/
    public function init()
    {
        /* The action */
        $this->subscribe('beforeSurveyPage');
        /* Survey settings */
        $this->subscribe('beforeSurveySettings');
        $this->subscribe('newSurveySettings');
    }

    /** @inheritdoc **/
    public function beforeSurveySettings()
    {
        if (!$this->getEvent()) {
            throw new CHttpException(403);
        }
        $oEvent = $this->event;
        $activeDefault = $this->get('active', null, null, $this->settings['active']['default']) ? gT('Yes') : gT('No');
        $oEvent->set("surveysettings.{$this->id}", array(
            'name' => get_class($this),
            'settings' => array(
                'active' => array(
                    'type' => 'select',
                    'label' => $this->translate("Reset submitted date when reloading a previouly submitted response."),
                    'help' => $this->translate("If survey allow reloading of reponse with token answser persistence : repponse is set as unsubmitted when reloaded"),
                    'options' => array(
                        1 => gT("Yes"),
                        0 => gT("No"),
                    ),
                    'htmlOptions' => array(
                        'empty' => CHtml::encode(sprintf($this->translate("Use default (%s)"), $activeDefault)),
                    ),
                    'current' => $this->get('active', 'Survey', $oEvent->get('survey'), "")
                ),
            )
        ));
    }

    /** @inheritdoc **/
    public function newSurveySettings()
    {
        if (!$this->getEvent()) {
            throw new CHttpException(403);
        }
        $event = $this->event;
        foreach ($event->get('settings') as $name => $value) {
            $this->set($name, $value, 'Survey', $event->get('survey'));
        }
    }

    /** @inheritdoc **/
    public function beforeSurveyPage()
    {
        if (!$this->getEvent()) {
            throw new CHttpException(403);
        }
        /* Need to be a post request */
        if (!App()->getRequest()->isPostRequest) {
            return;
        }
        $surveyid = $this->getEvent()->get('surveyId');
        /* Need to a srid in session */
        if (empty($_SESSION['survey_' . $surveyid]['srid'])) {
            return;
        }
        /* Need to be active */
        $active = $this->get('active', 'Survey', $surveyid, "");
        if ($active === "") {
            $active = $this->get('active', null, null, $this->settings['active']['default']);
        }
        if (empty($active)) {
            return;
        }
        /* Survey */
        $Survey = Survey::model()->findByPk($surveyid);
        /* Need not anonymisze + answer persistence + reload : not needed (except if use reloadAnyResponse) but more clear */
        if ($Survey->isAnonymized) {
            return;
        }
        if (!$Survey->isTokenAnswersPersistence) {
            return;
        }
        if (!$Survey->isAllowEditAfterCompletion) {
            return;
        }
        /* No need to check if token table : quickes session */
        if (empty($_SESSION['survey_' . $surveyid]['token'])) {
            return;
        }
        $srid = $_SESSION['survey_' . $surveyid]['srid'];
        Response::model($surveyid)->updateByPk(
            $srid,
            array('submitdate' => null)
        );
    }

    /**
    * @see parent::gT
    */
    private function translate($sToTranslate, $sEscapeMode = 'unescaped', $sLanguage = null)
    {
        return $this->gT($sToTranslate, $sEscapeMode, $sLanguage);
    }

}
