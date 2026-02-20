<?php

namespace UbeeDev\LibBundle\Service\AntiRobot;

use Symfony\Component\HttpFoundation\Request;

interface AntiRobotVerifierInterface
{
    /**
     * Vérifie si la requête provient d'un humain et non d'un robot
     *
     * @param Request $request La requête HTTP
     * @param array $parameters Les paramètres du formulaire
     * @return bool True si c'est un humain, false si c'est un robot
     */
    public function verify(Request $request, array $parameters): bool;

    /**
     * Retourne le nom du service de vérification
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Retourne les données nécessaires pour le template (clés publiques, etc.)
     *
     * @return array
     */
    public function getTemplateData(): array;
}