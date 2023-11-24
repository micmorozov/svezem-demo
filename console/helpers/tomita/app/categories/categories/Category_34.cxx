#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 34 - Рефрижераторные перевозки

Transp -> AnyWord<kwtype="перевезти">;
What -> "рефрижераторный" | "рефрижератор" | "температурный" | "температура";

//Fact -> Transp (AnyWord+) What;
Fact -> What;

S -> Fact interp(Category.Category_34);
