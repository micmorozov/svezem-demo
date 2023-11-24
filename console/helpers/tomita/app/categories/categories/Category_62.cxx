#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 62 - Перевозка емкостей

Transp -> AnyWord<kwtype="перевезти">;
What -> "емкость" | "бочка" | "цистерна";

Fact -> Transp (AnyWord+) What;

S -> Fact interp(Category.Category_62);