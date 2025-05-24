<?php

namespace Khalil1608\LibBundle\Controller;

use Khalil1608\LibBundle\Service\MarkdownParser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class PreviewMarkdownController extends AbstractController
{
    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function preview(Request $request, MarkdownParser $parser, Environment $environment): Response
    {
        return new Response($environment->render('@Khalil1608Lib/markdown-preview.html.twig', [
            'content' => $parser->parse($request->request->get('content'))
        ]));
    }
}