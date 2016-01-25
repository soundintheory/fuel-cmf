<?php

/**
 * The Contact Controller.
 *
 * @package  app
 * @extends  Controller
 */
class Controller_Page_Contact extends Controller_Base
{
    private $email;
    protected $details = array();
    protected $email_data;

    public function post_index(){
        $this->sent = true;

        $this->details = Input::post('contact');

        $this->createBody();
        $this->createMail();

        if($this->sendMail())
            $this->success = true;
        else
            $this->success = false;
    }

    private function createBody(){
        $this->email_data = \View::forge('emails/enquiry_html.twig', array('details'=> $this->details));
        $this->thank_you_email_data = \View::forge('emails/thank_you_enquiry_html.twig', array('details'=> $this->details));
    }

    private function createMail(){

        // Set the from address
        $this->email = Email::forge();
        $this->thank_you_email = Email::forge();

        // Set the from address
        $this->email->from($this->details['email'], $this->details['name']);
        $this->thank_you_email->to($this->details['email'], $this->details['name']);

       $this->settings_email = Model_settings::select('item.email')
            ->andWhere('item.visible=true')
            ->getQuery()
            ->getResult();

        $this->settings_email = $this->settings_email[0]['email'];

        // Set the to address
        $this->email->to($this->settings_email, 'Generic');
        $this->thank_you_email->from($this->settings_email, 'Generic');

        // Set a subject
        $this->email->subject('Generic Website Enquiry');
        $this->thank_you_email->subject('Thank You for your Enquiry');

        // And set the body.
        $this->email->html_body($this->email_data);
        $this->thank_you_email->html_body($this->thank_you_email_data);

    }

    private function sendMail(){
        try
        {
            $this->email->send();
        }
        catch(\EmailValidationFailedException $e)
        {
            return false;
        }
        catch(\EmailSendingFailedException $e)
        {
            return false;
        }
        $this->thank_you_email->send();
        return true;
    }

}