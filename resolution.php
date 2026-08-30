<?php
/**
 * Fonctions de résolution des redirections, extraites de index.php pour être
 * testables indépendamment du contrôleur front (sans phpCAS).
 *
 * Ces fonctions dépendent des variables globales $mapping, $CAS_attrs, $appli,
 * $DEV_MOD, render_access_denied_page() et de log_action()/log_lvl_to_int().
 */

function can_access($conf_property)
{
    // Vérifie s'il y a un filtre d'accés
    if (! array_key_exists('FILTER', $conf_property)) {
        return true;
    }
    if (evaluate_filter_rule($conf_property['FILTER'])) {
        log_action("DEBUG", "Le test du filtre est positif");
        return true;
    }
    log_action("INFO", "Le filtre interdit l'accès à l'utilisateur !");
    return false;
}

function evaluate_filter_rule($rule)
{
    if (!is_array($rule)) {
        throw new Exception("Un filtre a été défini mais celui-ci n'est pas correctement configuré.");
    }
    if (array_key_exists('USER_ATTRIBUTE', $rule) and array_key_exists('REGEX', $rule)) {
        return match_filter_condition($rule);
    }
    if (!array_key_exists('OPERATOR', $rule) or !array_key_exists('RULES', $rule) or !is_array($rule['RULES'])) {
        throw new Exception("Un filtre composé doit définir les propriétés OPERATOR et RULES.");
    }
    if (!is_string($rule['OPERATOR'])) {
        throw new Exception("L'opérateur du filtre composé doit être une chaîne.");
    }
    $operator = strtoupper($rule['OPERATOR']);
    if ($operator !== 'AND' and $operator !== 'OR') {
        throw new Exception("L'opérateur de filtre " . $rule['OPERATOR'] . " n'est pas supporté.");
    }
    if (empty($rule['RULES'])) {
        throw new Exception("Un filtre composé doit contenir au moins une règle.");
    }
    $i = 0;
    while ($i < sizeof($rule['RULES'])) {
        $match = evaluate_filter_rule($rule['RULES'][$i]);
        if ($operator === 'AND' and !$match) {
            return false;
        }
        if ($operator === 'OR' and $match) {
            return true;
        }
        $i++;
    }
    return $operator === 'AND';
}

function match_filter_condition($condition)
{
    global $CAS_attrs;
    $filter_attr = $condition['USER_ATTRIBUTE'];
    $regex = $condition['REGEX'];
    if (!is_string($filter_attr) || !is_string($regex)) {
        throw new Exception("Les propriétés USER_ATTRIBUTE et REGEX du filtre doivent être des chaînes.");
    }
    if (!is_array($CAS_attrs)) {
        throw new Exception("Le serveur CAS n'a pas retourné d'attributs exploitables pour le filtre.");
    }
    log_action("TRACE", "Le tableau de la condition de filtre est : " . print_r($condition, true));
    log_action("DEBUG", "Le nom de l'attribut CAS utilisé pour la condition est : " . $filter_attr);
    if (!array_key_exists($filter_attr, $CAS_attrs)) {
        log_action("TRACE", "Le tableau des attributs CAS fournis est : " . print_r($CAS_attrs, true));
        log_action("DEBUG", "Le serveur CAS n'a pas retourné l'attribut " . $filter_attr . " pour le filtre.");
        return false;
    }
    log_action("TRACE", "La valeur ou le tableau de valeurs pour l'attribut CAS utilisé est : " . print_r($CAS_attrs[$filter_attr], true));
    $cas_values = is_array($CAS_attrs[$filter_attr]) ? $CAS_attrs[$filter_attr] : array($CAS_attrs[$filter_attr]);
    foreach ($cas_values as $cas_value) {
        if ($cas_value === null || !is_scalar($cas_value) || trim((string) $cas_value) === '') {
            log_action("TRACE", "Ignore une valeur CAS vide ou non scalaire pour le filtre.");
            continue;
        }
        log_action("TRACE", "Teste l'appartenance de " . $cas_value);
        $match = @preg_match($regex, (string) $cas_value);
        if ($match === false) {
            throw new Exception("Expression régulière FILTER invalide : " . $regex);
        }
        if ($match === 1) {
            log_action("DEBUG", "Le test est positif");
            return true;
        }
        log_action("DEBUG", "Le test est négatif");
    }
    return false;
}

function do_replacement($conf_property, $chaine)
{
    global $CAS_attrs;
    // vérifie s'il y a des remplacements à réaliser
    if (! array_key_exists('REPLACE', $conf_property)) {
        return $chaine;
    } elseif (array_key_exists('REPLACE', $conf_property) and array_key_exists('USER_ATTRIBUTE', $conf_property['REPLACE'])) {
        $replacement_attr = $conf_property['REPLACE']['USER_ATTRIBUTE'];
        if (array_key_exists($replacement_attr, $CAS_attrs)) {
            log_action("TRACE", "L'attribut utilisateur nécessaire au remplacement de chaîne est fourni par le serveur CAS.");
            if (!is_array($CAS_attrs[$replacement_attr])) {
                $replacement_value = strtolower($CAS_attrs[$replacement_attr]);
                if (array_key_exists('VALUE_TO_LOWERCASE', $conf_property['REPLACE']) and !$conf_property['REPLACE']['VALUE_TO_LOWERCASE']) {
                    $replacement_value = strtoupper($CAS_attrs[$replacement_attr]);
                }
                $modif_chaine = str_ireplace('%' . $replacement_attr . '%', $replacement_value, $chaine);
                log_action("DEBUG", "Le remplacement de caractère sur la chaîne " . $chaine . " à retourné :" . $modif_chaine);
                return $modif_chaine;
            }
            $i = 0;
            log_action("ERROR", "Remplacement d'une chaîne par rapport à un attribut CAS contenant plusieurs valeurs pour l'attribut " . $replacement_attr . " !");
            log_action("TRACE", "Liste des valeurs CAS retournées : " . print_r($CAS_attrs[$replacement_attr], true));
            throw new Exception("L'attribut " . $replacement_attr . " contient plusieurs valeurs et ne peut pas être utilisé pour le remplacement.");
        }
        log_action("ERROR", "Le serveur CAS n'a pas retourné l'attribut " . $replacement_attr . " souhaité pour le remplacement. Les attributs fournis par le serveur CAS sont : " . implode(', ', array_keys($CAS_attrs)));
        log_action("TRACE", "Le tableau des attributs CAS fournis est : " . print_r($CAS_attrs, true));
        throw new Exception("Le serveur CAS n'a pas retourné l'attribut " . $replacement_attr . " nécessaire au remplacement.");
    }
    log_action("ERROR", "Une chaîne de remplacement a été définie mais celle-ci n'est pas correctement configurée avec l'attributs USER_ATTRIBUTE");
    throw new Exception("La règle REPLACE est incomplète.");
}

function do_redirect($conf_property, $url)
{
    global $appli, $DEV_MOD;
    if (is_null($url) || trim($url) === '' || strtolower(trim($url)) === 'null') {
        log_action("INFO", "Aucune redirection n'est définie pour l'application " . $appli . "  !");
        throw new Exception("Aucune redirection n'est définie pour l'application " . $appli . ".");
    }
    $url = do_replacement($conf_property, $url);
    log_action("INFO", "Le lien vers lequel rediriger l'utilisateur est : " . $url);
    if (!can_access($conf_property)) {
        log_action("ERROR", "L'utilisateur n'a pas les droits pour accéder à l'application " . $appli . "  !");
        throw new Exception("L'accès à l'application " . $appli . " est refusé par le filtre.");
    }
    if ($DEV_MOD) {
        header('Content-Type: text/plain; charset=utf-8;');
        echo 'header("Location: "' . $url . '", true, 302);';
        return;
    }
    header('Content-Type: text/html; charset=utf-8;');
    header('P3P:CP="IDC DSP COR ADM DEVi TAIi PSA PSD IVAi IVDi CONi HIS OUR IND CNT"');
    header("Location: " . $url, true, 302);
}

function find_default_link($conf_property)
{
    if (array_key_exists('DEFAULT_LINK', $conf_property)) {
        log_action("TRACE", "Nous sommes dans le cas de l'utilisation du lien par défaut.");
        return $conf_property['DEFAULT_LINK'];
    }
}

function find_domain_link($conf_property, $current_domain)
{
    if (!array_key_exists('DOMAIN_MAP', $conf_property) || !is_array($conf_property['DOMAIN_MAP'])) {
        return;
    }
    return array_key_exists($current_domain, $conf_property['DOMAIN_MAP']) ? $conf_property['DOMAIN_MAP'][$current_domain] : null;
}

function find_link_override($conf_property)
{
    global $CAS_attrs;
    if (!array_key_exists('LINK', $conf_property) || !is_array($conf_property['LINK'])) {
        return;
    }
    $override_attrs = array();
    if (array_key_exists('USER_ATTRIBUTE', $conf_property)) {
        $override_attrs[] = $conf_property['USER_ATTRIBUTE'];
    }
    if (array_key_exists('USER_ATTRIBUTE_FALLBACK', $conf_property)) {
        $override_attrs[] = $conf_property['USER_ATTRIBUTE_FALLBACK'];
    }
    $j = 0;
    while ($j < sizeof($override_attrs)) {
        $override_attr = $override_attrs[$j];
        if (array_key_exists($override_attr, $CAS_attrs)) {
            if (!is_array($CAS_attrs[$override_attr]) && array_key_exists($CAS_attrs[$override_attr], $conf_property['LINK'])) {
                return $conf_property['LINK'][$CAS_attrs[$override_attr]];
            } elseif (is_array($CAS_attrs[$override_attr])) {
                $possible_values = array_keys($conf_property['LINK']);
                $i = 0;
                while ($i < sizeof($possible_values)) {
                    if (in_array($possible_values[$i], $CAS_attrs[$override_attr])) {
                        return $conf_property['LINK'][$possible_values[$i]];
                    }
                    $i++;
                }
            }
        }
        $j++;
    }
}

function find_regex_link($conf_property, $user_attr = null)
{
    global $CAS_attrs;
    if (!array_key_exists('REGEX_LINK', $conf_property) || !is_array($conf_property['REGEX_LINK'])) {
        return;
    }
    $regex_attrs = array();
    if (!is_null($user_attr)) {
        $regex_attrs[] = $user_attr;
    } else {
        if (array_key_exists('USER_ATTRIBUTE', $conf_property)) {
            $regex_attrs[] = $conf_property['USER_ATTRIBUTE'];
        }
        if (array_key_exists('USER_ATTRIBUTE_FALLBACK', $conf_property)) {
            $regex_attrs[] = $conf_property['USER_ATTRIBUTE_FALLBACK'];
        }
    }
    $j = 0;
    while ($j < sizeof($regex_attrs)) {
        $regex_attr = $regex_attrs[$j];
        if (array_key_exists($regex_attr, $CAS_attrs)) {
            $cas_values = is_array($CAS_attrs[$regex_attr]) ? $CAS_attrs[$regex_attr] : array($CAS_attrs[$regex_attr]);
            foreach ($conf_property['REGEX_LINK'] as $regex => $link) {
                $i = 0;
                while ($i < sizeof($cas_values)) {
                    $match = @preg_match($regex, $cas_values[$i]);
                    if ($match === false) {
                        log_action("ERROR", "Expression régulière REGEX_LINK invalide : " . $regex);
                        throw new Exception("Configuration error !");
                    }
                    if ($match === 1) {
                        log_action("DEBUG", "REGEX_LINK " . $regex . " correspond à l'attribut " . $regex_attr . ".");
                        return $link;
                    }
                    $i++;
                }
            }
        }
        $j++;
    }
}

/**
 * Retourne l'url de redirection si OK, null si attribut utilisateur non existant
 * et throw exception si pas de droits d'accès.
 */
function find_cas_attr($user_attr, $appli)
{
    global $CAS_attrs, $mapping;
    if (array_key_exists($user_attr, $CAS_attrs)) {
        log_action("TRACE", "La valeur ou le tableau de valeurs pour l'attribut CAS utilisé est : " . print_r($CAS_attrs[$user_attr], true));
        log_action("TRACE", "L'attribut utilisateur nécessaire à la selection du lien est bien fourni par le serveur CAS.");
        if (! is_array($CAS_attrs[$user_attr]) and array_key_exists($CAS_attrs[$user_attr], $mapping[$appli]['LINK'])) {
            $cas_attr = $CAS_attrs[$user_attr];
            log_action("TRACE", "Nous ne sommes pas dans le cas d'un tableau de valeurs retournées par le serveur CAS !");
            return $mapping[$appli]['LINK'][$cas_attr];
        } elseif (is_array($CAS_attrs[$user_attr])) {
            /* S'il y a plusieurs valeurs on prend la première qui vient, c'est pour cela qu'il faut configurer en premier dans le fichier conf.inc.php les propriétées prioritaire */
            $possible_val_user_attr = array_keys($mapping[$appli]['LINK']);
            $found = false;
            $i = 0;
            log_action("TRACE", "Nous sommes dans le cas d'un tableau de valeurs retournée pas le CAS");
            log_action("DEBUG", "Liste des propriétés définies à tester : " . implode(', ', $possible_val_user_attr));
            while (!$found and $i < sizeof($possible_val_user_attr)) {
                log_action("TRACE", "Teste l'appartenance de " . $possible_val_user_attr[$i]);
                if (in_array($possible_val_user_attr[$i], $CAS_attrs[$user_attr])) {
                    $found = true;
                    $cas_attr = $possible_val_user_attr[$i];
                    log_action("DEBUG", "Le teste est positif");
                } else {
                    log_action("DEBUG", "Le teste est négatif");
                }
                $i++;
            }
            if (! $found) {
                log_action("DEBUG", "Aucun LINK exact n'a été trouvé pour l'application " . $appli . " et l'attribut CAS " . $user_attr . ".");
                log_action("TRACE", "La valeur CAS non configurée pour l'attribut " . $user_attr . " est : " . $CAS_attrs[$user_attr]);
            } else {
                return $mapping[$appli]['LINK'][$cas_attr];
            }
        }
        if (!is_null($regex_link = find_regex_link($mapping[$appli], $user_attr))) {
            return $regex_link;
        } elseif (!is_null($default_link = find_default_link($mapping[$appli]))) {
            // Cas où rien n'a été trouvé en fonction de l'attribut utilisateur, dans ce cas on prend la valeur par défaut si celle-ci est définie.
            return $default_link;
            // cas du !array_key_exists($CAS_attrs[$user_attr],$mapping[$appli]['LINK']) and !is_array($CAS_attrs[$user_attr])
        } else {
            log_action("ERROR", "Aucune propriétée n'a été définie pour l'application " . $appli . " et l'attribut CAS choisi " . $user_attr . ", vérifiez la configuration (par exemple l'association profil/url dans le fichier conf.inc.php).");
            log_action("TRACE", "La valeur CAS non configurée pour l'attribut " . $user_attr . " est : " . $CAS_attrs[$user_attr]);
            throw new Exception("Configuration error !");
        }
    } else {
        log_action("ERROR", "Le serveur CAS n'a pas retourné l'attribut " . $user_attr . " souhaité pour l'application " . $appli . ". Les attributs fournis par le serveur CAS sont : " . implode(', ', array_keys($CAS_attrs)));
        log_action("TRACE", "Le tableau des attributs CAS fournis est : " . print_r($CAS_attrs, true));
        return;
    }
}
