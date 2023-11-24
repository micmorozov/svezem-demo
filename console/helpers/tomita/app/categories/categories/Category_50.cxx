#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 50 - Перевозка на дачу

Transp -> AnyWord<kwtype="перевезти">;
What -> "дача";

Fact -> Transp (AnyWord+) What;

S -> Fact interp(Category.Category_50);