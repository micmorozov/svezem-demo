#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 30 - Перевозка молока

Transp -> AnyWord<kwtype="перевезти">;
What -> "молоко";

Fact -> Transp (AnyWord+) What;

S -> Fact interp(Category.Category_30);