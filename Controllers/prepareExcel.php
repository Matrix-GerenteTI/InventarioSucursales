<?php
require_once $_SERVER['DOCUMENT_ROOT']."/intranet/lib/PHPExcel.php";
require_once $_SERVER['DOCUMENT_ROOT']."/intranet/lib/PHPExcel/Writer/Excel2007.php";
require_once $_SERVER['DOCUMENT_ROOT'].'/intranet/lib/phpmailer/class.phpmailer.php';

class PrepareExcel
{
    public $libro;

    public $bordes = array( 'borders' => array(
                'allborders' => array(
                            'style' => \PHPExcel_Style_Border::BORDER_THIN
                        )
                    )
                );

    public $centrarTexto = array(
        'alignment' => array(
            'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER_CONTINUOUS,
        )
    );

    protected  $labelBold = array(  'font' => 
                                                        array( 'bold' => true,
                                                                    'size' => 10,
                                                                    'name' => 'Arial'), );
    protected $borderBottom = array('borders' =>
                                         array('bottom' => 
                                                array('style' =>PHPExcel_Style_Border::BORDER_THICK,'color' => array('rgb' => '000000'),)));                                                                    
    public function __construct( )
    {
        $this->libro = new PHPExcel;
        $this->libro->getProperties()->setCreator('Intranet');
        $this->libro->getProperties()->setLastModifiedBy("Intranet");
    
    }

    public function setColorFill( $bgColor)
    {
        return      array(
            'type' => PHPExcel_Style_Fill::FILL_SOLID,
                    'startcolor' => array(
                        'rgb' =>  $bgColor
                    ) );
    }

    public function setColorText( $color,$size = 12)
    {
        return array(               'font'  => array(
                            'color' => array('rgb' => $color),
                            'size'  => $size,
                            'name'  => 'Arial'
        ));
    }

       public function creaEmptySheet( $titulo="", $index = null  )
    {
    
        if ( $index != null ) {
            $this->libro->createSheet( $index );
            $this->setActiveSheetAndSetTitle( $index, $titulo);
        } else {
            
            $this->setActiveSheetAndSetTitle( 0, $titulo);
        }
    }
    
    public function setActiveSheetAndSetTitle( $index, $titulo)
    {

            $this->libro->setActiveSheetIndex( $index );
            $this->libro->getActiveSheet()->setTitle( $titulo );

    }

    public function putLogo( $coordenada, $width, $height)
    {
            $objDrawing1 = new PHPExcel_Worksheet_Drawing();
            $objDrawing1->setName('Logo');
            $objDrawing1->setDescription('Logo');
            $objDrawing1->setPath($_SERVER['DOCUMENT_ROOT']."/inventario/assets/images/logo.png");
            $objDrawing1->setCoordinates( $coordenada);	 
            $objDrawing1->setOffsetX(100);
            $objDrawing1->setHeight($height);
            $objDrawing1->setWidth( $width);					
            $objDrawing1->setWorksheet( $this->libro->getActiveSheet() );
    }

    public function getMesAsString( $mes)
    {
        $mes = $mes / 1;
        $meses = array('-','ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO','JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE');
        return $meses[$mes];
    }

      public function enviarReporte( $configCorreo)
    {
        extract( $configCorreo );
        $emailsender = new phpmailer;
        $emailsender->isSMTP();
        $emailsender->SMTPDebug = 0;
        $emailsender->SMTPAuth = true;
        $emailsender->Port = 587;

        $emailsender->Host = 'mail.matrix.com.mx';
        $emailsender->Username = "no-responder@matrix.com.mx";
        $emailsender->Password = "M@tr1x2017";

        $emailsender->From ="no-responder@matrix.com.mx";
        $emailsender->FromName = $descripcionDestinatario;

        $emailsender->Subject ="$subject";
        $emailsender->Body = "<p>$mensaje</p>";

        $emailsender->AltBody = "...";

        if ( is_file($pathFile) ) {
            $emailsender->AddAttachment( $pathFile);
        }
        //sestrada
        foreach ($correos as $email) {
            $emailsender->AddAddress( $email );
        }
        // $emailsender->AddAddress("auxsistemas@matrix.com.mx");
		// // $emailsender->AddAddress("jefeinventario@matrix.com.mx");
		// // $emailsender->AddAddress("raulmatrixxx@hotmail.com");
		
        $statusEnvio = $emailsender->Send();

        if ( $emailsender->ErrorInfo == "SMTP Error: Data not accepted") {
            $statusEnvio = true;
        } 

        if ( !$statusEnvio ) {
            // var_dump( $statusEnvio );
             echo "[".$emailsender->ErrorInfo."] - Problemas enviando correo electrónico a ";
        } else {
            echo "Enviado";
        }
    }
}
