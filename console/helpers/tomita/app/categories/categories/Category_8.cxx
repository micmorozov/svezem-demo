#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 8 - Перевозка строительных материалов

Transp -> AnyWord<kwtype="перевезти">;
What -> "строительный материал" | "цемент" | "кирпич" | "свая" | "ригель";

Fact -> Transp (AnyWord+) What;

S -> Fact interp(Category.Category_8);
