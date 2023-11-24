#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 13 - Перевозка негабаритных грузов

Transp -> AnyWord<kwtype="перевезти">;
What -> "негабаритный" | "высокий";

Fact -> Transp (AnyWord+) What;

S -> Fact interp(Category.Category_13);