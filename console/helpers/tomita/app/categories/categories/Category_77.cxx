#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 77 - Перевозка каблуком и портером

Fact -> "каблук" | "портер";

S -> Fact interp(Category.Category_77);
