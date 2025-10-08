-- Filtrer les éléments de formatage (gras, italique, souligné, barré)
function Strong(elem)
  return elem.content
end

function Emph(elem)
  return elem.content
end

function Underline(elem)
  return elem.content
end

function Strikeout(elem)
  return elem.content
end

-- Garder seulement les liens vers des sites web (http/https)
function Link(elem)
  local url = elem.target
  if string.match(url, "^https?://") then
    return elem
  else
    return elem.content
  end
end

-- Supprimer toutes les balises HTML brutes
function Span(elem)
  return elem.content
end

function RawInline(elem)
  if elem.format == "html" then
    return {}
  end
  return elem
end

function RawBlock(elem)
  if elem.format == "html" then
    return {}
  end
  return elem
end

-- Extraire le texte d'un élément
function extract_text(elem)
  if type(elem) == "string" then
    return elem
  elseif elem.t == "Str" then
    return elem.text
  elseif elem.t == "Space" then
    return " "
  elseif elem.t == "SoftBreak" or elem.t == "LineBreak" then
    return "\n"
  elseif elem.content then
    local text = ""
    for _, item in ipairs(elem.content) do
      text = text .. extract_text(item)
    end
    return text
  elseif elem.c then
    return extract_text(elem.c)
  end
  return ""
end

-- Vérifier si une ligne contient seulement un caractère répété
function is_single_repeated_char(text)
  local trimmed = string.gsub(text, "^%s+", "")
  trimmed = string.gsub(trimmed, "%s+$", "")
  if trimmed == "" then
    return false
  end
  local first_char = string.sub(trimmed, 1, 1)
  local pattern = "^" .. string.gsub(first_char, "([%^%$%(%)%%%.%[%]%*%+%-%?])", "%%%1") .. "+$"
  return string.match(trimmed, pattern) ~= nil
end

-- Vérifier si un bloc est vide
function is_empty_block(block)
  if block.t == "Para" or block.t == "Plain" then
    local text = extract_text(block)
    return text == "" or string.match(text, "^%s*$")
  end
  return false
end

-- Détecter un début de citation
function starts_with_quote(text)
  return string.match(text, "^%s*«") ~= nil
end

-- Détecter une fin de citation
function ends_with_quote(text)
  return string.match(text, "»%s*$") or -- »
         string.match(text, "»%s*%.%s*$") or -- ».
         string.match(text, "»%s*%(.+%)%s*$") or -- » ()
         string.match(text, "»%s*%.%s*%(.+%)%s*$") or -- ». ()
         string.match(text, "»%s*%(.+%)%s*%.%s*$") -- » ().
end

-- Détecter une fin de citation sur plusieurs lignes
function has_open_paren_after_quote(text)
  return string.match(text, "»%s*%.%s*%(") or -- ». (
         string.match(text, "»%s*%(") -- » (
end

function ends_with_close_paren(text)
  return string.match(text, "%)%s*%.%s*$") or -- ).
         string.match(text, "%)%s*$") -- )
end

-- Ignorer les lignes avec un seul caractère répété
function Para(elem)
  local text = extract_text(elem)
  if is_single_repeated_char(text) then
    return {}
  end
  return elem
end

-- Ignorer les titres avec un seul caractère répété
function Header(elem)
  local text = extract_text(elem)
  if is_single_repeated_char(text) then
    return {}
  end
  return elem
end

-- Enlever tous les backslash d'échappement Markdown
function Str(elem)
  elem.text = string.gsub(elem.text, "\\([%.%(%)%[%]%*_#%+`-])", "%1")
  return elem
end

-- Ignorer les lignes horizontales
function HorizontalRule(elem)
  return {}
end

-- Grouper les paragraphes en citations et limiter le nombre de lignes vides
function Pandoc(doc)

  local new_blocks = {}
  local quote_blocks = {}
  local in_quote = false
  local quote_started = false
  local waiting_for_close_paren = false
  local consecutive_empty_lines = 0

  for i, block in ipairs(doc.blocks) do
    if block.t == "Para" or block.t == "Plain" or block.t == "BulletList" then

      local text = extract_text(block)

      if is_single_repeated_char(text) then

        -- Si on était dans une citation, on la termine (sauf si on attend une parenthèse)
        if in_quote and quote_started and not waiting_for_close_paren then
          if #quote_blocks > 0 then
            table.insert(new_blocks, pandoc.BlockQuote(quote_blocks))
          end
          quote_blocks = {}
          in_quote = false
          quote_started = false
        end
        consecutive_empty_lines = 0
      elseif text == "" or string.match(text, "^%s*$") then -- Ligne vide
        if in_quote then

          -- Ajouter la ligne vide dans la citation
          table.insert(quote_blocks, block)
        else

          -- Limiter à 2 lignes vides consécutives
          if consecutive_empty_lines < 2 then
            table.insert(new_blocks, block)
            consecutive_empty_lines = consecutive_empty_lines + 1
          end
        end
      else -- Ligne avec du contenu
        consecutive_empty_lines = 0

        -- Si on attend la fermeture d'une parenthèse
        if waiting_for_close_paren then

          table.insert(quote_blocks, block)

          -- Vérifier si cette ligne contient la parenthèse fermante
          if ends_with_close_paren(text) then

            -- Fin de la citation
            table.insert(new_blocks, pandoc.BlockQuote(quote_blocks))
            quote_blocks = {}
            in_quote = false
            quote_started = false
            waiting_for_close_paren = false
          end
        elseif starts_with_quote(text) then

          -- Début d'une citation
          in_quote = true
          quote_started = true
          table.insert(quote_blocks, block)

          -- Vérifier si la citation se termine sur la même ligne
          if ends_with_quote(text) then
            table.insert(new_blocks, pandoc.BlockQuote(quote_blocks))
            quote_blocks = {}
            in_quote = false
            quote_started = false
          elseif has_open_paren_after_quote(text) and not ends_with_close_paren(text) then

            -- Parenthèse ouverte après ». mais pas fermée sur la même ligne
            waiting_for_close_paren = true
          end
        elseif in_quote then
          -- On est dans une citation
          table.insert(quote_blocks, block)

          -- Vérifier si c'est la fin de la citation
          if ends_with_quote(text) then
            table.insert(new_blocks, pandoc.BlockQuote(quote_blocks))
            quote_blocks = {}
            in_quote = false
            quote_started = false
          elseif has_open_paren_after_quote(text) and not ends_with_close_paren(text) then
            -- Parenthèse ouverte après ». mais pas fermée sur la même ligne
            waiting_for_close_paren = true
          end
        else
          -- Paragraphe normal
          table.insert(new_blocks, block)
        end
      end
    elseif block.t == "Header" then
      local text = extract_text(block)

      consecutive_empty_lines = 0

      -- Terminer une citation en cours si nécessaire (sauf si on attend une parenthèse)
      if in_quote and quote_started and not waiting_for_close_paren then
        if #quote_blocks > 0 then
          table.insert(new_blocks, pandoc.BlockQuote(quote_blocks))
        end
        quote_blocks = {}
        in_quote = false
        quote_started = false
      end

      -- Ajouter le header s'il n'est pas un caractère répété
      if not is_single_repeated_char(text) then
        table.insert(new_blocks, block)
      end
    elseif block.t == "HorizontalRule" then

      -- Ignorer les lignes horizontales
      consecutive_empty_lines = 0

      if in_quote and quote_started and not waiting_for_close_paren then
        if #quote_blocks > 0 then
          table.insert(new_blocks, pandoc.BlockQuote(quote_blocks))
        end
        quote_blocks = {}
        in_quote = false
        quote_started = false
      end
    elseif block.t == "BlockQuote" then

      -- Si c'est déjà une citation, la traiter
      local quote_text = extract_text(block)
      consecutive_empty_lines = 0

      -- Terminer une citation en cours si nécessaire (sauf si on attend une parenthèse)
      if in_quote and quote_started and not waiting_for_close_paren then
        if #quote_blocks > 0 then
          table.insert(new_blocks, pandoc.BlockQuote(quote_blocks))
        end
        quote_blocks = {}
        in_quote = false
        quote_started = false
      end

      -- Vérifier si cette citation commence par « et finit par »
      if starts_with_quote(quote_text) and ends_with_quote(quote_text) then
        table.insert(new_blocks, block)
      else

        -- Convertir en paragraphes normaux
        for _, content_block in ipairs(block.content) do
          table.insert(new_blocks, content_block)
        end
      end
    elseif block.t == "RawBlock" and block.format == "html" then

      -- Ignorer les blocs HTML
      consecutive_empty_lines = 0
    else
      consecutive_empty_lines = 0

      -- Terminer une citation en cours si nécessaire (sauf si on attend une parenthèse)
      if in_quote and quote_started and not waiting_for_close_paren then
        if #quote_blocks > 0 then
          table.insert(new_blocks, pandoc.BlockQuote(quote_blocks))
        end
        quote_blocks = {}
        in_quote = false
        quote_started = false
      end

      table.insert(new_blocks, block)
    end
  end

  -- Terminer une citation en cours à la fin du document
  if in_quote and quote_started and #quote_blocks > 0 then
    table.insert(new_blocks, pandoc.BlockQuote(quote_blocks))
  end

  doc.blocks = new_blocks
  return doc
end

return {
  {Span = Span, Strong = Strong, Emph = Emph, Underline = Underline, Strikeout = Strikeout, Link = Link, Para = Para, Header = Header, HorizontalRule = HorizontalRule, RawInline = RawInline, RawBlock = RawBlock, Str = Str},
  {Pandoc = Pandoc}
}
