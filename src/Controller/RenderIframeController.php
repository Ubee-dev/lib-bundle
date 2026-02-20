<?php


namespace UbeeDev\LibBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RenderIframeController extends AbstractController
{
    #[Route('/test/iframe/{iframeType}', name: 'test_render_iframe')]
    public function success(string $iframeType): Response
    {
        return new Response(
            '<html><body>'.$iframeType.'</body></html>'
        );
    }
}