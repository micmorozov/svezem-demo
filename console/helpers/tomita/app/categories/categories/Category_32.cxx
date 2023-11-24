#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 32 - Перевозка мяса

Transp -> AnyWord<kwtype="перевезти">;
What -> "мясо" | "колбаса";

Fact -> Transp (AnyWord+) What;

S -> Fact interp(Category.Category_32);